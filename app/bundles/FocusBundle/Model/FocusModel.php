The user wants me to update the `FocusModel.php` file.
The issue is that "Contactfield tokens for values of the corresponding company do not work in focus items".
The user provided a snippet of the file and mentioned that the "Auditor rejected solution" (which seems to be the provided snippet, though it looks incomplete).

I need to understand the context of the issue.
Focus items (like popups, forms) often use tokens like `{contactfield=firstname}`.
The issue states that these tokens don't work for company fields when the contact is associated with a company.
Usually, in Mautic, when processing tokens for a contact, one might need to check if the token refers to a company field and if so, retrieve the value from the associated company.

Looking at the provided snippet, it's just the class definition and constructor properties. It doesn't show the logic for token replacement.

I need to find the method responsible for generating the content or processing tokens for focus items.
Common methods in FormModel or similar models that handle rendering or processing might be `getEntity`, `getSampleValues`, or specific methods for building the view.

Let's look at the structure of a typical Mautic FormModel or FocusModel.
Focus items are entities. The model likely has a method to generate the HTML/JS for the focus item.
Inside that generation logic, tokens are usually replaced.

The issue specifically mentions "Contactfield tokens for values of the corresponding company".
This implies that somewhere in the code, tokens are being replaced using the Contact object, but Company fields are not being checked or mapped.

Let's search for the file content in my internal knowledge base or simulate finding it.
The file path is `app/bundles/FocusBundle/Model/FocusModel.php`.

I need to see the full content of the file to identify where the token replacement happens.

Hypothetical scenario:
The `getViewProperties` or `getEntity` or a method called `getSampleValues` is used to render the focus item.
In that method, there is likely a loop or a regex replacing tokens.
The logic probably looks like:
`$content = str_replace('{contactfield=...}', $contact->getFieldValue(...), $content);`
The problem is that `$contact->getFieldValue(...)` might not handle company fields, or the logic simply doesn't look at the company.

The auditor rejected a solution. The provided snippet is just the class skeleton. It doesn't contain the logic.
Wait, the prompt says "Output the COMPLETE updated content."
The provided snippet ends abruptly at `AuthorizationCheckerInterface $`.

I need to generate the full file content based on the standard Mautic FocusModel, but modified to fix the company token issue.

Standard FocusModel methods:
- `getName`
- `getRepository`
- `getEntity`
- `getSampleValues` (This is often where sample data is generated for the builder)
- `getViewProperties` (This generates the JS/HTML)

Let's look at `getSampleValues`. This method is crucial for the focus builder to show previews.
It likely creates a dummy lead and populates fields.
If it populates fields, it might need to create a dummy company and populate company fields too.

Let's look at `getViewProperties`. This method takes the entity and generates the array for the frontend.
It likely calls a helper to replace tokens.

The specific issue: "Contactfield tokens for values of the corresponding company do not work".
This phrasing is slightly ambiguous. Does it mean tokens like `{contactfield=companyname}` (which is actually a company field linked to contact) or tokens specifically for company fields?
Usually, in Mautic, you can use `{contactfield=companyname}` if the field is mapped, or `{companyfield=companyname}`.
However, the issue says "Contactfield tokens for values of the corresponding company". This suggests that the user is using a contact field token (e.g. `{contactfield=company_industry}`) expecting it to pull from the company, or perhaps the system is supposed to automatically resolve company fields when using contact tokens.

Actually, looking at Mautic history, there was a time where company fields weren't automatically available in all contexts via contact tokens, or the logic was missing.

Let's assume the fix involves ensuring that when tokens are processed, if the field belongs to a company, the value is retrieved from the associated company.

Where does this logic live?
In `FocusModel`, there is likely a method `getSampleValues`.
Let's check the standard implementation of `getSampleValues` in Mautic FocusBundle.

    public function getSampleValues(Focus $entity)
    {
        $sample = [
            'id'        => 0,
            'ip'        => '192.168.0.1',
            'link'      => '#',
        ];

        $fields = $entity->getProperties()['form']['fields'] ?? [];
        foreach ($fields as $key => $field) {
            $sample[$field['alias']] = $this->getSampleValue($field);
        }

        return $sample;
    }

And `getSampleValue`:
    protected function getSampleValue(array $field)
    {
        // ... logic to generate fake data ...
    }

If the issue is about *preview* not working, it's in `getSampleValues`.
If the issue is about *live* execution, it's in the controller or a hook.

However, the issue description "Contactfield tokens for values of the corresponding company do not work in focus items" suggests that during the rendering of the focus item (popup), the tokens are not being replaced correctly with company data.

Let's look at the `getViewProperties` method. This is where the "content" of the focus item is prepared.
It likely calls a token replacement service.

Wait, the auditor rejected solution snippet provided in the prompt is just the class properties. This suggests the previous attempt might have been incomplete or wrong.

I need to find the actual file content to modify it correctly.
I will assume the file is the standard Mautic FocusModel.
I will look for the `getSampleValues` method and the `getViewProperties` method.

In `getSampleValues`, we need to ensure we are generating sample data for company fields if they are present in the form configuration.
In `getViewProperties` (or wherever the actual token replacement happens for the frontend), we need to ensure the company object is available or passed to the token replacement logic.

Actually, the most common place this fails is in the `getSampleValues` method used by the builder UI. If you configure a field to use a company token, the preview won't show it because the sample lead doesn't have a company.

Let's check if `FocusModel` has a method `getViewProperties`.
Yes, it does.
It generates the HTML/JS.
It likely uses `Mautic\PageBundle\Helper\TokenHelper` or similar.

However, the specific mention of "Contactfield tokens for values of the corresponding company" implies that the user is using `{contactfield=...}` syntax for fields that are actually company fields, or the system is expected to resolve them.

Let's look at the `getSampleValues` method.
If the form has a field with alias `companyname`, and it's a company field, `getSampleValues` needs to return a value for it.
If the logic only looks at lead fields, it won't.

Let's look at the `getViewProperties` method.
It probably calls `$this->getSampleValues($entity)` to get the values to inject into the content.

The fix likely involves modifying `getSampleValues` to also check for company fields and generate sample data for them, or modifying the token replacement logic to handle company fields.

Let's look at the provided snippet again. It defines dependencies.
`LeadModel`, `CompanyModel` are defined.
So the model has access to Company data.

The `getSampleValues` method signature is `public function getSampleValues(Focus $entity)`.
It iterates over form fields.
If a field is a company field, we need to generate a sample value.

But how do we know if a field is a company field?
Usually, the field definition in the focus item properties has a type or alias.
Or we query the LeadFieldRepository to check if it's a company field.

Let's assume the standard Mautic implementation.
The `getSampleValues` method usually looks like this:

    public function getSampleValues(Focus $entity)
    {
        $sample = [
            'id'        => 0,
            'ip'        => '192.168.0.1',
            'link'      => '#',
        ];

        $fields = $entity->getProperties()['form']['fields'] ?? [];
        foreach ($fields as $key => $field) {
            $sample[$field['alias']] = $this->getSampleValue($field);
        }

        return $sample;
    }

And `getSampleValue` generates random strings, etc.

The issue is that if `{contactfield=companyname}` is used, and `companyname` is a company field, the `getSampleValue` method might not be generating a value for it, or the logic checking if it's a company field is missing.

Wait, the issue says "Contactfield tokens for values of the corresponding company".
This might mean the user is using `{contactfield=company_field_alias}`.
In Mautic, you can map company fields to contact fields.
But if the token is directly `{contactfield=...}`, it usually refers to the contact table.
If the user wants company data, they should use `{companyfield=...}`.
However, if the system is supposed to support `{contactfield=...}` resolving to company data (which is a common request/complaint), that's a different logic.

Let's stick to the most likely fix: The `getSampleValues` method needs to be aware of company fields.
If the field alias corresponds to a company field, we should generate a sample value for it.

How to check if a field is a company field?
Inject `LeadFieldModel`? Or `FieldModel`.
The model has `LeadModel` and `CompanyModel`.
We can use `LeadModel->getFieldModel()->getFields()` to get all fields.
Filter by 'object' => 'company'.

Let's look at the `FocusModel` code structure.
It likely extends `FormModel`.
`FormModel` has `getSampleValues`.

I will reconstruct the file.
I need to ensure `getSampleValues` handles company fields.

Also, there is a method `getViewProperties`.
It generates the content.
It might use `PageHelper` or similar to replace tokens.
If the issue is in the *live* view (not just preview), the token replacement logic in the controller/bundle needs to pass the company object.

However, usually `FocusModel` handles the generation of the view array.
Let's look at `getViewProperties`.
It calls `$this->getSampleValues($entity)` to get the values.
Then it likely does string replacement.

If the auditor rejected the solution, maybe the previous solution was incomplete.
The provided snippet is just the top of the class.

Let's try to find the specific logic in Mautic FocusBundle.
The file `FocusModel.php` is responsible for generating the popup content.

The method `getViewProperties` usually looks like this:
    public function getViewProperties(Focus $entity)
    {
        $properties = $entity->getProperties();
        // ... logic to build html/js ...
        $content = $properties['html']; // or similar
        // Token replacement
        $values = $this->getSampleValues($entity);
        foreach ($values as $token => $value) {
            $content = str_replace('{contactfield='.$token.'}', $value, $content);
        }
        // ...
    }

If the issue is that company fields don't work, it's because `getSampleValues` doesn't populate them, or the token replacement regex doesn't handle company tokens.

But the issue title says "Contactfield tokens for values of the corresponding company".
This implies the user is using `{contactfield=...}` expecting company data.
Or, more likely, the system is failing to resolve company fields that are mapped or available.

Let's look at the `getSampleValues` method in the standard Mautic FocusBundle.
It iterates over fields.
If a field is a company field, it needs to be handled.

Actually, looking at the Mautic source code for `FocusBundle/Model/FocusModel.php`:
The `getSampleValues` method exists.
It loops through fields.
It calls `getSampleValue`.

The fix usually involves checking if the field is a company field.
To do this, we need the `LeadFieldModel` (or `FieldModel`).
The `FocusModel` has `LeadModel`. `LeadModel` has `getFieldModel()`.

So, in `getSampleValues`, we should check:
1. Get all fields (lead and company).
2. If the current field is a company field, generate a company sample value.

Wait, the issue might be simpler.
The `getSampleValues` method might just be missing the logic to generate values for company fields entirely.
If I add logic to detect if a field is a company field and generate a value for it, that would fix the preview.
For the live view, the token replacement logic needs to be robust.

Let's assume the fix is in `getSampleValues`.
I need to modify `getSampleValues` to handle company fields.

Algorithm:
1. Get fields from entity properties.
2. Get all lead fields and company fields from `LeadFieldModel`.
3. For each field in entity:
    a. Check if it's a lead field or company field.
    b. Generate appropriate sample value.

However, `FocusModel` might not have `LeadFieldModel` injected directly, but it has `LeadModel`.
`LeadModel` has `getFieldModel()`.

Let's write the code for `getSampleValues`.

    public function getSampleValues(Focus $entity)
    {
        $sample = [
            'id'        => 0,
            'ip'        => '192.168.0.1',
            'link'      => '#',
        ];

        $fields = $entity->getProperties()['form']['fields'] ?? [];
        
        // Get fields to identify company fields
        $allFields = $this->leadModel->getFieldModel()->getFields(
            [
                'filter' => [
                    'force' => [
                        [
                            'column' => 'f.object',
                            'expr'   => 'in',
                            'value'  => ['lead', 'company'],
                        ],
                    ],
                ],
            ]
        );
        
        // Create a map of aliases to objects
        $fieldAliases = [];
        foreach ($allFields as $f) {
            $fieldAliases[$f['alias']] = $f['object'];
        }

        foreach ($fields as $key => $field) {
            $alias = $field['alias'];
            if (isset($fieldAliases[$alias]) && 'company' === $fieldAliases[$alias]) {
                // It's a company field
                $sample[$alias] = $this->getSampleCompanyValue($field);
            } else {
                // It's a lead field (or default)
                $sample[$alias] = $this->getSampleValue($field);
            }
        }

        return $sample;
    }

I need to implement `getSampleCompanyValue`.
It can be similar to `getSampleValue` but tailored for company data (strings, etc).

Also, I need to check `getViewProperties`.
Does it use `getSampleValues`?
Yes, typically it does.
If `getSampleValues` is fixed, the preview works.
Does the live view use `getSampleValues`?
Usually, the live view uses the actual Lead/Company objects.
The issue might be that in the live view, the tokens are replaced using the Contact object, and the Contact object doesn't automatically expose company fields unless specifically accessed.

However, the issue title specifically mentions "focus items".
In Mautic, Focus items are rendered via JS.
The JS requests the content from the server? Or is it embedded?
Focus items are usually embedded HTML/JS.
The tokens are replaced on the server side before sending to the browser.

If the tokens are replaced on the server side, the code must have access to the Lead and Company.
The `FocusModel` is a service. It might be called from a controller with the current lead.

Let's look at the `getViewProperties` method in the real file.
It takes `Focus $entity`.
It doesn't take a Lead object.
This suggests `getSampleValues` is ONLY for the preview/builder.
The live rendering happens elsewhere?
Actually, `FocusModel` has a method `getJavascript`? No.

Let's look at how focus items are displayed.
There is a `FocusHelper` or similar?
Actually, looking at `app/bundles/FocusBundle/Controller/FocusController.php`:
The `view` action renders the focus item.
It calls `$model->getViewProperties($focus)`.
This method returns an array of properties.
It likely contains the HTML content with tokens replaced.

If `getViewProperties` doesn't take a Lead object, how does it replace tokens with real data?
It probably uses `getSampleValues` for the preview, but for the live view, it might use a different mechanism or the tokens are replaced client-side?
No, Mautic tokens are usually server-side.

Wait, if `getViewProperties` doesn't take a Lead, it can't replace tokens with *real* user data. It can only replace them with *sample* data or static text.
Unless... the focus item content is static, and only the *form fields* are dynamic?
No, the issue says "Contactfield tokens ... do not work".
This implies they are expected to work.

Maybe the issue is that `getViewProperties` *should* take a Lead?
Or maybe the token replacement happens in a subscriber?

Let's reconsider the "Auditor rejected solution" part.
The provided snippet is just the class properties.
Maybe the previous solution was to add a method `getSampleCompanyValue`?
Or maybe the previous solution was to modify `getSampleValues` but did it wrong?

Let's look at the file content I need to generate.
I will assume the standard Mautic `FocusModel.php` content.
I will modify `getSampleValues` to handle company fields.

Standard `getSampleValues`:
    public function getSampleValues(Focus $entity)
    {
        $sample = [
            'id'        => 0,
            'ip'        => '192.168.0.1',
            'link'      => '#',
        ];

        $fields = $entity->getProperties()['form']['fields'] ?? [];
        foreach ($fields as $key => $field) {
            $sample[$field['alias']] = $this->getSampleValue($field);
        }

        return $sample;
    }

I need to inject logic to detect company fields.
To do this, I need `LeadFieldModel`.
I can get it from `LeadModel`.

    public function getSampleValues(Focus $entity)
    {
        $sample = [
            'id'        => 0,
            'ip'        => '192.168.0.1',
            'link'      => '#',
        ];

        $fields = $entity->getProperties()['form']['fields'] ?? [];
        
        // Get all fields to identify company fields
        $leadFields = $