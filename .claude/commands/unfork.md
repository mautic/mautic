## Unfork merge commit from staging branch to new branch from 7.x branch

1. **Fetch Merge Commit**
    - Merge commit hash from `staging` branch (e.g., `7a0c1fcdce80a03acaeec3209051c8a5e297b042`) would be passed as argument
2. **Create New Working Branch in `mautic`**
    - ☐ In the `mautic` directory, fetch the latest code from upstream and reset the 7.x branch:
      ```bash
      git checkout 7.x
      git pull mautic 7.x
      ```
    - ☐ Create and switch to a **new git branch** based on the clean 7.x branch:
      ```bash
      git checkout -b branch-name
      ```
        - **Branch naming:** Ask for a branch name.
3. **Cherry-Pick Merge Commit**
    - ☐ Use the merge commit hash from step 1 (e.g., `7a0c1fcdce80a03acaeec3209051c8a5e297b042`)
    - ☐ Cherry-pick the merge commit with:
      ```bash
      git cherry-pick -x <MERGE_COMMIT_HASH> -m 1
      ```
    - ☐ If cherry-pick fails due to conflicts, resolve them manually.
4. **Conflict Resolution**
    - ☐ Carefully resolve all code conflicts during cherry-pick.
    - ☐ If uncertain about a conflict, **ask for clarification before proceeding**.
5. **Exclusions, Removals, and Clean-up**
    - During cherry-pick and after resolving conflicts, ensure:
        - ☐ **No code changes from `AllydeBundle` are included.**
        - ☐ **Remove or ignore all code changes to:**
            - `AllydeBundle/`
            - `CalendarBundle`
            - `app/config/hosted.php`
            - `app/config/parameters_local.php`
            - `themes/system` (remove the entire `system` directory inside the `themes` folder)
        - ☐ Double-check diff/commits to ensure these files/directories are not present in the final result.
        - `config.php` file changes can be skipped since Symfony autoconfig/autowiring will handle the service registration.
        - Do not make any additional changes unless absolutely necessary.
6. **Code Quality & Compatibility**
    - ☐ Run PHPStan on the modified files to check for potential issues:
      ```bash
      ddev composer phpstan -- --memory-limit=2G path/to/modified/files
      ```
    - ☐ Ensure all code adheres to **Mautic 7.x and Symfony 6.x coding standards**.
      ```bash
      ddev composer fixcs -- path/to/directory_or_file
      ```
    - ☐ All code must comply with the **latest Symfony best practices** as used in `mautic` 7.x.
    - ☐ Use modern PHP syntax (typed properties, return type declarations, etc.)
    - ☐ DO NOT convert PHP 7.4+ typed property syntax back to PHPDoc comments
    - ☐ DO NOT remove or modify type declarations in function signatures
    - ☐ Preserve modern syntax and type hints unless they cause compatibility issues
    - ☐ ONLY make changes that are DIRECTLY related to the PR being ported
    - ☐ DO NOT modify service names, function signatures, or config entries unless specifically changed in the original PR
    - ☐ When in doubt, compare with the original PR diff to ensure you're only porting required changes
    - ☐ Apply any necessary changes or refactoring to meet these standards, if required.
    - ☐ Commit any CS fixes separately for clarity.
7. **Testing**
    - ☐ Ensure all tests included in the PR pass successfully after migration.
      ```bash
      ddev composer test -- path/to/modified/test/files
      ```
    - ☐ Update, add, or remove tests as necessary for the new context.
8. **Final Review**
    - ☐ Review the code for completeness, correctness, and overall quality.
    - ☐ Confirm all exclusions/clean-up steps have been respected.
9. **Create Community Draft PR**
    - ☐ Push the changes from the new branch to git remote mautic-fork (forked repo on Github):
      ```bash
      git push mautic-fork branch-name
      ```
    - ☐ Open a **Draft PR** by visiting the URL provided in the push output, or directly at:
      ```
      https://github.com/username/mautic/pull/new/branch-name
      ```
    - ☐ Write the PR description based on the original PR:
        - Target the `7.x` branch in the mautic/mautic repository
        - Mark as draft PR
        - **Do not include any "MAUT" references** in the description
        - Include clear test steps

---

## Important Notes

- **Commit Messages:** Use detailed, clear messages explaining the cherry-pick and any manual conflict resolutions or code modifications. **IMPORTANT: NEVER include any MAUT references in commit messages or PR descriptions.**
- **Strict Exclusion:** Do **not** carry forward any part of `AllydeBundle`, config files specific to hosting, or the `themes/system` directory.
- **Code Standards:** All code must comply with current Mautic and Symfony guidelines, both syntactically and structurally.
- **Testing:** Ensure the migrated feature is properly covered by automated tests.
- **Service Registration:** In Mautic 7.x (based on Symfony 6.x), services are autowired by default. If a class implements `EventSubscriberInterface`, it will be automatically registered as an event subscriber without needing explicit declaration in `config.php`. Even when a bundle config.php has a 'services' section in older PRs, you may not need to add it in the 7.x port.

---

## Quick Reference Exclusion Table

| Exclude/Remove                  | Description                                      |
|----------------------------------|--------------------------------------------------|
| `AllydeBundle/`                 | Any directory, file, or code related to Allyde   |
| `CalendarBundle/`                 | Any directory, file, or code related to CalendarBundle   |
| `app/config/hosted.php`         | Hosted-specific config file                      |
| `app/config/parameters_local.php`| Local parameters config file                     |
| `themes/system`                 | System directory under themes                    |

---

*Use this checklist and guidance strictly. If you encounter any ambiguities or additional conflicts not addressed here, pause and ask for clarification before proceeding.*