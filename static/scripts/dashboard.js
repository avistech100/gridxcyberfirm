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


document.addEventListener('DOMContentLoaded', (e)=> {
    const menuEl = document.querySelector('.menu');
    if (menuEl) {
        menuEl.addEventListener('click', (ev) => {
            // Only trigger loading animation if the sidebar is expanded (opened === true)
            if (typeof opened !== 'undefined' && !opened) return;

            const li = ev.target.closest('li');
            if (!li) return;

            const siblings = Array.from(li.parentNode.children);
            const index = siblings.indexOf(li);

            // Index mapping:
            // 0: Dashboard
            // 1: Phone Files
            // 2: Location Tracking
            // 3: Social Apps
            // 4: Video Apps
            // 5: Live Recording
            // 6: Remote Control
            const menuFeatures = {
                0: { key: 'dashboard', name: 'Dashboard', iconClass: 'fa-solid fa-gauge', subtext: '' },
                1: { key: 'files', name: 'Phone Files', iconClass: 'fa-solid fa-folder', subtext: '' },
                2: { key: 'location', name: 'Location Tracking', iconClass: 'fa-solid fa-location-dot', subtext: 'Please wait while we track the location' },
                3: { key: 'social', name: 'Social Apps', iconClass: 'fa-solid fa-comments', subtext: '' },
                4: { key: 'video', name: 'Video Apps', iconClass: 'fa-solid fa-video', subtext: '' },
                5: { key: 'recording', name: 'Live Recording', iconClass: 'fa-solid fa-microphone', subtext: 'Initializing live session...' },
                6: { key: 'control', name: 'Remote Control', iconClass: 'fa-solid fa-desktop', subtext: 'Connecting to device...' }
            };

            if (menuFeatures[index]) {
                ev.stopPropagation();
                ev.preventDefault();
                const feat = menuFeatures[index];
                let config = { iconClass: feat.iconClass, subtext: feat.subtext };
                
                // Custom SVG/HTML structures for side menu overlays
                if (feat.key === 'dashboard') {
                    config.iconSvg = `
                        <svg class="brand-overlay-logo" viewBox="0 0 24 24" style="width: 80px; height: 80px;">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z" fill="#FFFFFF"/>
                        </svg>
                    `;
                } else if (feat.key === 'files') {
                    config.iconSvg = `
                        <svg class="brand-overlay-logo" viewBox="0 0 24 24" style="width: 80px; height: 80px;">
                            <path d="M10 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z" fill="#FFB300"/>
                        </svg>
                    `;
                } else if (feat.key === 'location') {
                    config.iconSvg = `
                        <svg class="brand-overlay-logo" viewBox="0 0 24 24" style="width: 80px; height: 80px;">
                            <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" fill="#2196F3"/>
                        </svg>
                    `;
                } else if (feat.key === 'social') {
                    config.iconSvg = `
                        <svg class="brand-overlay-logo" viewBox="0 0 24 24" style="width: 80px; height: 80px;">
                            <path d="M4 18h16c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2zm1-12h4v4H5V6zm0 6h4v4H5v-4zm6-6h4v4h-4V6zm0 6h4v4h-4v-4zm6-6h4v4h-4V6zm0 6h4v4h-4v-4z" fill="#333333"/>
                        </svg>
                    `;
                } else if (feat.key === 'video') {
                    config.iconSvg = `
                        <svg class="brand-overlay-logo" viewBox="0 0 24 24" style="width: 80px; height: 80px;">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z" fill="#FFFFFF"/>
                        </svg>
                    `;
                } else if (feat.key === 'recording') {
                    config.iconSvg = `
                        <svg class="brand-overlay-logo" viewBox="0 0 24 24" style="width: 80px; height: 80px;">
                            <circle cx="12" cy="12" r="10" fill="none" stroke="#FF5252" stroke-width="2"/>
                            <circle cx="12" cy="12" r="5" fill="#FF5252"/>
                        </svg>
                    `;
                } else if (feat.key === 'control') {
                    config.iconSvg = `
                        <svg class="brand-overlay-logo" viewBox="0 0 24 24" style="width: 80px; height: 80px;">
                            <path d="M21 3H3c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h5v2h8v-2h5c1.1 0 1.99-.9 1.99-2L23 5c0-1.1-.9-2-2-2zm0 14H3V5h18v12zM10 7.5l6 4.5-6 4.5v-9z" fill="#FFFFFF"/>
                        </svg>
                    `;
                }

                if (window.showLoadingOverlay) {
                    window.showLoadingOverlay(feat.key, feat.name, config);
                }
            }
        });
    }
});


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



