function fixDuplicateHeadContent() {
  const head = document.head;
  const body = document.body;

  if (!head || !body) return;

  // Clone the <head> content
  const clonedHeadContent = head.cloneNode(true);

  // Remove script and link elements from cloned content to avoid duplication
  const scripts = clonedHeadContent.querySelectorAll('script');
  const links = clonedHeadContent.querySelectorAll('link');
  scripts.forEach(script => {
    script.remove();
  });
  links.forEach(link => {
    link.remove();
  });

  // Append the modified <head> content to the <body>
  body.insertBefore(clonedHeadContent, body.firstChild);
}

document.addEventListener('DOMContentLoaded', fixDuplicateHeadContent);
