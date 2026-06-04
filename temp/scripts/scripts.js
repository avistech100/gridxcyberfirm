const open = document.getElementById('li-display');
const close = document.getElementById('cancel');
const items = document.getElementById('li');

open.addEventListener('click', ()=>{
  // open.style.display = 'none';
  open.style.opacity = '0';
  open.style.visibility = 'hidden';
  // items.style.display = 'flex'
  items.style.opacity = '1';
  items.style.visibility = 'visible';
})

close.addEventListener('click', ()=>{
  // items.style.display = 'none';
  items.style.opacity = '0';
  items.style.visibility = 'hidden';
  // open.style.display = 'flex'
  open.style.opacity = '1';
  open.style.visibility = 'visible';
})

const tablinks = document.getElementsByClassName('tab-links')
const tabcontents = document.getElementsByClassName('tabs-contents')

function openTab(tabname) {
    for (tablink of tablinks) {
        tablink.classList.remove("active-link");
    }

    for (tabcontent of tabcontents) {
        tabcontent.classList.remove("active-tab");
    }

    event.currentTarget.classList.add('active-link');
    document.getElementById(tabname).classList.add("active-tab");
}


// const slider =document.querySelector('.review-list');
// const slide =document.querySelectorAll('.review-list div');

// let currentSlide = 2;

// slide[currentSlide].classList.add('active');

// const changeSlide = ()=>{
//     slide[currentSlide].classList.remove('active');
    
//     currentSlide = (currentSlide + 1) % slide.length;
    
//     slide[currentSlide].classList.add('active');

//     slide.forEach((slides, index) => {
//         if (index === currentSlide){
//             slides.style.transform = 'translateX(0)';
//         }else if (index < currentSlide) {
//             slides.style.transform = 'translateX(-100%)';
//         }else{
//             slides.style.transform = 'translateX(100%)';
//         }
//     })

// }

// setInterval(changeSlide, 5000)


const carousel = document.querySelector('.carousel');
const carouselItems = document.querySelectorAll('.carousel-item');
const prevBtn = document.querySelector('.prev-btn');
const nextBtn = document.querySelector('.next-btn');
const dotsContainer = document.querySelector('.dots-container');

let currentSlide = 0;

// Create dots
for (let i = 0; i < carouselItems.length; i++) {
  const dot = document.createElement('div');
  dot.classList.add('dot');
  if (i === currentSlide) {
    dot.classList.add('active');
  }
  dotsContainer.appendChild(dot);
}

const dots = document.querySelectorAll('.dot');

// Function to show the next slide
function showNextSlide() {
  carouselItems[currentSlide].classList.remove('active');
  dots[currentSlide].classList.remove('active');
  currentSlide = (currentSlide + 1) % carouselItems.length;
  carouselItems[currentSlide].style.animation = 'flip .5s ease-in-out .5s';
  carouselItems[currentSlide].classList.add('active');
  dots[currentSlide].classList.add('active');
}

// Function to show the previous slide
function showPrevSlide() {
  carouselItems[currentSlide].classList.remove('active');
  dots[currentSlide].classList.remove('active');
  currentSlide = (currentSlide - 1 + carouselItems.length) % carouselItems.length;
  carouselItems[currentSlide].classList.add('active');
  dots[currentSlide].classList.add('active');
}

// Add event listeners to buttons
nextBtn.addEventListener('click', showNextSlide);
prevBtn.addEventListener('click', showPrevSlide);

// Add event listeners to dots
dots.forEach((dot, index) => {
  dot.addEventListener('click', () => {
    carouselItems[currentSlide].classList.remove('active');
    dots[currentSlide].classList.remove('active');
    currentSlide = index;
    carouselItems[currentSlide].classList.add('active');
    dots[currentSlide].classList.add('active');
  });
});

// Autoplay
setInterval(showNextSlide, 7000);