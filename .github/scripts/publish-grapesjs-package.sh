#!/usr/bin/env bash
#
# Pushes each tag to the split repo as one commit that adds the compiled dist directory
# on top of the split commit. Split branches stay source-only.
#
# Usage (from the monorepo root, full history):
#   SPLITSH=<splitsh-lite> publish-grapesjs-package.sh check-existing|publish <tag>
#   publish-grapesjs-package.sh verify-packagist <tag> <commit>
#
# Env: TARGET (default: gitsplit config), PACKAGIST_TIMEOUT (default: 1800 s).

set -euo pipefail

PREFIX=plugins/GrapesJsBuilderBundle
DIST=Assets/library/js/dist
PACKAGE=mautic/grapes-js-builder-bundle
SPLIT_CONFIG=.github/workflows/gitsplit/plugin-grapesjs.json
CI_NAME='Mautic CI'
CI_EMAIL='ci@mautic.org'

fail() {
    echo "ERROR: $*" >&2
    exit 1
}

validate_dist_dir() {
    local dir=$1 key file excluded

    [[ -f "$dir/manifest.json" ]] || fail "$dir/manifest.json is missing"

    for key in builder.js builder.css; do
        file=$(jq -er --arg key "$key" '.[$key] | strings' "$dir/manifest.json") \
            || fail "manifest.json has no \"$key\" entry"
        [[ "$file" == "$(basename -- "$file")" && "$file" != '..' ]] \
            || fail "manifest.json entry \"$key\" is not a plain file name: $file"
        [[ -f "$dir/$file" && -s "$dir/$file" ]] || fail "$dir/$file is missing or empty"
    done

    excluded=$(find "$dir" -name node_modules -o -name .parcel-cache)
    [[ -z "$excluded" ]] || fail "build-only files found in $dir: $excluded"
}

# Single parent = split commit, changes only in dist, valid dist.
verify_commit() {
    local split=$1 commit=$2 parents outside tmp

    parents=$(git rev-list --parents -n 1 "$commit" | cut -d' ' -f2-)
    [[ "$parents" == "$split" ]] || fail "$commit must have exactly one parent $split, found: ${parents:-none}"

    outside=$(git diff --name-only "$split" "$commit" | grep -v "^$DIST/" || true)
    [[ -z "$outside" ]] || fail "$commit changes files outside $DIST/:"$'\n'"$outside"

    tmp=$(mktemp -d)
    git archive "$commit" "$DIST" | tar -x -C "$tmp"
    validate_dist_dir "$tmp/$DIST"
    rm -rf "$tmp"

    echo "Verified distribution commit $commit (parent $split)."
}

verify_remote_tag() {
    local target=$1 tag=$2 split=$3 commit

    git fetch --quiet --no-tags "$target" "+refs/tags/$tag:refs/grapesjs-dist/$tag"
    commit=$(git rev-parse "refs/grapesjs-dist/$tag^{commit}")
    verify_commit "$split" "$commit"
    write_output commit "$commit"
}

write_output() {
    echo "$1: $2"
    if [[ -n "${GITHUB_OUTPUT:-}" ]]; then
        echo "$1=$2" >>"$GITHUB_OUTPUT"
    fi
}

# Sets TARGET_URL and SPLIT_COMMIT.
resolve_split() {
    local tag=$1

    [[ -x "${SPLITSH:-}" ]] || fail "SPLITSH must point to the splitsh-lite binary"
    TARGET_URL=${TARGET:-$(jq -er '."subtree-splits"[0].target' "$SPLIT_CONFIG")}

    SPLIT_COMMIT=$("$SPLITSH" --prefix="$PREFIX" --origin="tags/$tag") || fail "splitsh-lite failed for tags/$tag"
    [[ -n "$SPLIT_COMMIT" ]] || fail "splitsh-lite returned no commit for tags/$tag"
    echo "Split commit for $tag: $SPLIT_COMMIT"
}

# 0 = exists, 1 = missing, exit on read error.
remote_tag_exists() {
    local target=$1 tag=$2 status=0

    git ls-remote --exit-code --tags "$target" "refs/tags/$tag" >/dev/null || status=$?
    [[ $status -eq 0 || $status -eq 2 ]] || fail "Unable to read tags from $target"

    return $((status != 0))
}

# A published tag is never rebuilt or replaced.
check_existing() {
    local tag=$1

    resolve_split "$tag"
    if remote_tag_exists "$TARGET_URL" "$tag"; then
        echo "Tag $tag already exists in $TARGET_URL. Verifying it without rebuilding."
        verify_remote_tag "$TARGET_URL" "$tag" "$SPLIT_COMMIT"
        write_output published true
    else
        write_output published false
    fi
}

publish() {
    local tag=$1 index tree commit tag_object

    resolve_split "$tag"
    # Tag may have been published after check-existing.
    if remote_tag_exists "$TARGET_URL" "$tag"; then
        echo "Tag $tag already exists in $TARGET_URL. Verifying it without rebuilding."
        verify_remote_tag "$TARGET_URL" "$tag" "$SPLIT_COMMIT"

        return
    fi

    validate_dist_dir "$PREFIX/$DIST"

    # Temporary index: split tree + ignored dist directory.
    index=$(mktemp -u)
    GIT_INDEX_FILE="$index" git read-tree "$SPLIT_COMMIT"
    GIT_INDEX_FILE="$index" git -C "$PREFIX" --git-dir="$(git rev-parse --absolute-git-dir)" --work-tree=. add -f -- "$DIST"
    tree=$(GIT_INDEX_FILE="$index" git write-tree)
    rm -f "$index"

    commit=$(GIT_AUTHOR_NAME="$CI_NAME" GIT_AUTHOR_EMAIL="$CI_EMAIL" GIT_COMMITTER_NAME="$CI_NAME" GIT_COMMITTER_EMAIL="$CI_EMAIL" \
        git commit-tree "$tree" -p "$SPLIT_COMMIT" -m "Add compiled GrapesJS assets for $tag")
    verify_commit "$SPLIT_COMMIT" "$commit"

    # git tag cannot be used: the monorepo already has this tag name.
    tag_object=$(printf 'object %s\ntype commit\ntag %s\ntagger %s <%s> %s +0000\n\nTag %s\n' \
        "$commit" "$tag" "$CI_NAME" "$CI_EMAIL" "$(date +%s)" "$tag" | git mktag)

    # No force push: verify the other run's tag instead.
    if ! git push "$TARGET_URL" "$tag_object:refs/tags/$tag"; then
        echo "Pushing tag $tag was rejected. Verifying the existing remote tag."
        verify_remote_tag "$TARGET_URL" "$tag" "$SPLIT_COMMIT"

        return
    fi

    write_output commit "$commit"
}

verify_packagist() {
    local tag=$1 commit=$2 deadline reference dir installed

    deadline=$(($(date +%s) + ${PACKAGIST_TIMEOUT:-1800}))
    while true; do
        reference=$(curl -fsS "https://repo.packagist.org/p2/$PACKAGE.json" \
            | jq -r --arg package "$PACKAGE" --arg version "$tag" \
                '.packages[$package][] | select(.version == $version) | .source.reference' || true)
        if [[ "$reference" == "$commit" ]]; then
            echo "Packagist lists $PACKAGE $tag at $commit."
            break
        fi
        if (($(date +%s) >= deadline)); then
            fail "Packagist does not list $PACKAGE $tag at $commit (found: ${reference:-nothing})"
        fi
        sleep 30
    done

    dir=$(mktemp -d)
    # Replace core-lib to download only the plugin.
    jq -n --arg package "$PACKAGE" --arg version "$tag" \
        '{require: {($package): $version}, replace: {"mautic/core-lib": "*"}, config: {"allow-plugins": false}}' \
        >"$dir/composer.json"
    COMPOSER_HOME="$dir/.composer" COMPOSER_CACHE_DIR="$dir/.cache" \
        composer update --working-dir="$dir" --no-interaction --no-scripts --prefer-dist

    installed=$(jq -r --arg package "$PACKAGE" '.packages[] | select(.name == $package) | .dist.reference' "$dir/composer.lock")
    [[ "$installed" == "$commit" ]] || fail "Installed reference $installed is not $commit"
    validate_dist_dir "$dir/vendor/$PACKAGE/$DIST"
    rm -rf "$dir"

    echo "Clean installation of $PACKAGE $tag contains valid assets."
}

case "${1:-}" in
    check-existing)
        [[ $# -eq 2 ]] || fail "Usage: $0 check-existing <tag>"
        check_existing "$2"
        ;;
    publish)
        [[ $# -eq 2 ]] || fail "Usage: $0 publish <tag>"
        publish "$2"
        ;;
    verify-packagist)
        [[ $# -eq 3 ]] || fail "Usage: $0 verify-packagist <tag> <commit>"
        verify_packagist "$2" "$3"
        ;;
    *)
        fail "Usage: $0 check-existing <tag> | publish <tag> | verify-packagist <tag> <commit>"
        ;;
esac
