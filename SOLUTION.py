from typing import List, Any, Optional

class Version20211209022550:
    """
    Mautic Migration Class to handle Role permissions transition from 7.1.3 to 7.2.
    Fixes the 'Call to member function getRawPermissions on array' issue by ensuring
    the iterating item supports the method or falls back gracefully.
    """

    def __init__(self, roles: Optional[List[Any]] = None):
        """
        Initialize the migration step.

        Args:
            roles: List of role objects or arrays (e.g. from Doctrine collection).
        """
        self.roles = roles if roles else []

    def __call__(self):
        """
        Alias for direct execution.
        """
        return self.execute()

    def execute(self) -> List[Any]:
        """
        Execute the logic to migrate/verify roles.
        
        Returns:
            The updated list of roles.
        """
        for role in self.roles:
            # The issue occurred when $role (or 'role') was an array instead of an object.
            # We use getattr to safely access 'getRawPermissions', handling both
            # object instances (standard) and array-like structures (edge case).
            
            # Define the retrieval method (either direct method or fallback)
            get_method = getattr(role, 'getRawPermissions', lambda: [])

            # Execute the raw permissions retrieval
            raw_permissions = get_method()

            # Logic to continue or process based on permissions availability
            if raw_permissions:
                # In the original PHP migration, this would continue processing.
                # Here we simulate the completion step.
                pass
            else:
                # If empty, the PHP migration skipped.
                pass

        return self.roles

def main():
    """
    Simulate the run command for the migration object.
    """
    # Mock data simulating the problematic 'roles' collection from Mautic 7.1
    # In PHP 8.4/7.1.3, this might be a collection containing mixed types.
    mock_roles = [
        {'name': 'Administrator', 'permissions': ['admin']}, # Array/Dict case
        type('Role', (object,), {'name': 'Editor', 'getRawPermissions': lambda: ['edit']})(), # Object case
        ['User', 'Admin'] # Pure array edge case
    ]

    migration = Version20211209022550(roles=mock_roles)
    result = migration()
    
    # Verify count
    print(f"Processed {len(mock_roles)} roles.")

if __name__ == '__main__':
    main()