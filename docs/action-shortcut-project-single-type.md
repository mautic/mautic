# Project single-type association shortcut

When a user can edit only one kind of entity in a project, the Add entity action
opens that entity's picker directly. Projects with more than one editable entity
type keep the existing type-selection modal.

Before, the user opened Add entity, selected the only available type, and then
waited for the picker. Now the picker opens directly. The server still checks
the project permission and validates the entity type before showing the form.
