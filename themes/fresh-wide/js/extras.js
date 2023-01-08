/*-- Adding Fixed Header --*/

function resizeHeaderOnScroll() {
  const distanceY = window.pageYOffset || document.documentElement.scrollTop,
  shrinkOn = 1,
  headerEl = document.getElementById('top');
  
  if (distanceY > shrinkOn) {
    headerEl.classList.add("scroll");
  } else {
    headerEl.classList.remove("scroll");
  }
}

window.addEventListener('scroll', resizeHeaderOnScroll);
