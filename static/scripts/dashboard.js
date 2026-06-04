{/* <aside class="sidebar">
            <div class="logo">
                <img src="{% static 'images/SVGs/user.svg' %}" alt="dummy user Image">
                <span>
                    {% if user.is_authenticated %}
                        <p>{{ user.username }}</p>
                    {% else %}
                        <strong>John</strong>
                    {% endif %}
                </span>
            </div>
            

            <ul class="menu">
                <li class="active"><i class="fa-solid fa-gauge"></i> Dashboard</li>
                <li><i class="fa-solid fa-folder"></i> Phone Files</li>
                <li><i class="fa-solid fa-location-dot"></i> Location Tracking</li>
                <li><i class="fa-solid fa-comments"></i> Social Apps</li>
                <li><i class="fa-solid fa-video"></i> Video Apps</li>
                <li><i class="fa-solid fa-microphone"></i> Live Recording</li>
                <li><i class="fa-solid fa-desktop"></i> Remote Control</li>
                <li><i class="fa-solid fa-file-export"></i> Data Export</li>
                <li><i class="fa-solid fa-shield-halved"></i> Permissions Check</li>
            </ul>
        </aside> */}


document.addEventListener('DOOMContentLoaded', (e)=> {
    document.querySelector('.menu').addEventListener('click', ()=> openModal())
})


let sidebar = document.querySelector('.sidebar');
let menu = document.querySelector('.menu');
let content = document.querySelector('.content');
let user = document.querySelector('.logo span');


sidebar.addEventListener('click', ()=>{
    opened = true
    sidebar.style.width = '240px';
    menu.style.fontSize = '1.125rem'
    user.style.display = 'flex'
    menu.innerHTML =`
    <li class="active"><i class="fa-solid fa-gauge"></i> Dashboard</li>
    <li><i class="fa-solid fa-folder"></i> Phone Files</li>
    <li><i class="fa-solid fa-location-dot"></i> Location Tracking</li>
    <li><i class="fa-solid fa-comments"></i> Social Apps</li>
    <li><i class="fa-solid fa-video"></i> Video Apps</li>
    <li><i class="fa-solid fa-microphone"></i> Live Recording</li>
    <li><i class="fa-solid fa-desktop"></i> Remote Control</li>
    <li><i class="fa-solid fa-file-export"></i> Data Export</li>
    <li><i class="fa-solid fa-shield-halved"></i> Permissions Check</li>
    `
})

user.style.display = 'none'
content.addEventListener('click', ()=>{
    opened=false
    sidebar.style.width = '72px';
    menu.style.fontSize = '1.125rem'
    user.style.display = 'none'
    menu.innerHTML = `
    <li class="active"><i class="fa-solid fa-gauge"></i></li>
    <li><i class="fa-solid fa-folder"></i></li>
                <li><i class="fa-solid fa-location-dot"></i></li>
                <li><i class="fa-solid fa-comments"></i></li>
                <li><i class="fa-solid fa-video"></i></li>
                <li><i class="fa-solid fa-microphone"></i></li>
                <li><i class="fa-solid fa-desktop"></i></li>
                <li><i class="fa-solid fa-file-export"></i></li>
                <li><i class="fa-solid fa-shield-halved"></i></li>
            `
})



