To resolve the issue of the Leads API not returning owned contacts when using the `lead:leads:viewown` permission, we need to modify the API endpoint to filter contacts based on the `owner_id` instead of `createdBy`. 

Here's the exact code fix:

```php
// In the ContactsApi.php file, update the getContacts() method
public function getContacts()
{
    // ...
    
    // Replace the following line
    // $contacts = $this->repository->getContactsCreatedBy($this->getUser()->getId());
    
    // With this
    $contacts = $this->repository->getContactsOwnedBy($this->getUser()->getId());
    
    // ...
}

// In the ContactRepository.php file, add a new method
public function getContactsOwnedBy($ownerId)
{
    $q = $this->getEntityManager()->createQueryBuilder('c');
    $q->select('c')
        ->from('MauticLeadBundle:Lead', 'c')
        ->where('c.ownerId = :ownerId')
        ->setParameter('ownerId', $ownerId);
    
    return $q->getQuery()->getResult();
}
```

Alternatively, you can also modify the existing `getContactsCreatedBy()` method to use the `getPermissionUser()` method, which checks for both `owner` and `createdBy`:

```php
// In the ContactRepository.php file, update the getContactsCreatedBy() method
public function getContactsCreatedBy($userId)
{
    $q = $this->getEntityManager()->createQueryBuilder('c');
    $q->select('c')
        ->from('MauticLeadBundle:Lead', 'c')
        ->where('c.ownerId = :userId OR c.createdBy = :userId')
        ->setParameter('userId', $userId);
    
    return $q->getQuery()->getResult();
}
```

This will ensure that the API returns contacts that are either owned by or created by the current user, aligning with the `getPermissionUser()` method.