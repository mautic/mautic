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

    // Iterate over each child in the head
    headChildren.forEach((headChild) => {
      // Find duplicates in the body
      const duplicateInBody = bodyElement.querySelector(
        `${headChild.tagName}[src='${headChild.src}'], ${headChild.tagName}[href='${headChild.href}']`
      );

      // If a duplicate is found, remove it from the body
      if (duplicateInBody) {
        bodyElement.removeChild(duplicateInBody);
      }
    });
  } catch (error) {
    console.error('Error fixing head content duplication:', error);
  }
}

// Call the function to fix the issue
document.addEventListener('DOMContentLoaded', fixHeadContentDuplication);