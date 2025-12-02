// Function to fix the issue of <head> content duplication into <body>
function fixHeadContentDuplication() {
  try {
    // Select the <head> element
    const headElement = document.head;
    // Select the <body> element
    const bodyElement = document.body;

    // Check if headElement and bodyElement exist
    if (!headElement || !bodyElement) {
      throw new Error('Head or Body element not found');
    }

    // Get all children of the <head> element
    const headChildren = Array.from(headElement.children);

    // Iterate over each child in the <head> element
    headChildren.forEach(child => {
      // Check if the child is mistakenly duplicated in the <body>
      const duplicateInBody = bodyElement.querySelector(child.tagName);
      if (duplicateInBody) {
        // Remove the duplicate from the <body>
        bodyElement.removeChild(duplicateInBody);
      }
    });
  } catch (error) {
    console.error('Error fixing head content duplication:', error);
  }
}

// Call the function to fix the issue
fixHeadContentDuplication();