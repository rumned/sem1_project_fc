const carousel = document.querySelector(".main-page-carousel ul");
const dotsContainer = document.getElementById("carousel-dots");
const items = carousel.children;

// Calculate number of pages (2 items per page on desktop)
const itemsPerPage = 2;
const totalPages = Math.ceil(items.length / itemsPerPage);
let currentPage = 0;

// Create dots
for (let i = 0; i < totalPages; i++) {
  const dot = document.createElement("div");
  dot.className = "dot";
  if (i === 0) dot.classList.add("active");
  dot.addEventListener("click", () => goToPage(i));
  dotsContainer.appendChild(dot);
}

// Navigate to specific page
function goToPage(page) {
  currentPage = page;
  const scrollAmount = (carousel.scrollWidth / totalPages) * page;
  carousel.scrollTo({ left: scrollAmount, behavior: "smooth" });
  updateDots();
}

// Update active dot
function updateDots() {
  const dots = dotsContainer.children;
  for (let i = 0; i < dots.length; i++) {
    dots[i].classList.toggle("active", i === currentPage);
  }
}

// Track scroll position and update dots
carousel.addEventListener("scroll", () => {
  // Check if carousel is actually scrollable
  if (carousel.scrollWidth <= carousel.clientWidth) return;

  const scrollPercentage =
    carousel.scrollLeft / (carousel.scrollWidth - carousel.clientWidth);
  const newPage = Math.round(scrollPercentage * (totalPages - 1));
  if (newPage !== currentPage) {
    currentPage = newPage;
    updateDots();
  }
});
