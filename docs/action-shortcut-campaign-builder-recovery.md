# Campaign builder recovery after a failed update

When the campaign builder cannot update its connections, the builder now removes
the loading state and enables its controls again. The current canvas changes are
kept in the page, but nothing is submitted automatically. The error shown by the
existing AJAX handler remains available so the user can retry or leave the
builder deliberately.

The normal close flow still removes the overlay and restores the controls after
both successful and failed connection updates.
