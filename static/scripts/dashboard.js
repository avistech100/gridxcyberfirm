let sidebar = document.querySelector('.sidebar');
let menu = document.querySelector('.menu');
let content = document.querySelector('.content');

// Features map for sidebar menu items (by their position index in the list, 0 to 8)
const menuFeatures = {
    0: {
        key: 'dashboard', name: 'Dashboard',
        iconSvg: `<svg class="brand-overlay-logo" viewBox="0 0 24 24" style="width:80px;height:80px;"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z" fill="#FFFFFF"/></svg>`,
        subtext: ''
    },
    1: {
        key: 'files', name: 'Phone Files',
        iconSvg: `<svg class="brand-overlay-logo" viewBox="0 0 24 24" style="width:80px;height:80px;"><path d="M10 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z" fill="#FFB300"/></svg>`,
        subtext: ''
    },
    2: {
        key: 'location', name: 'Location Tracking',
        iconSvg: `<svg class="brand-overlay-logo" viewBox="0 0 24 24" style="width:80px;height:80px;"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" fill="#2196F3"/></svg>`,
        subtext: 'Please wait while we track the location'
    },
    3: {
        key: 'social', name: 'Social Apps',
        iconSvg: `<svg class="brand-overlay-logo" viewBox="0 0 24 24" style="width:80px;height:80px;"><path d="M4 18h16c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2zm1-12h4v4H5V6zm0 6h4v4H5v-4zm6-6h4v4h-4V6zm0 6h4v4h-4v-4zm6-6h4v4h-4V6zm0 6h4v4h-4v-4z" fill="#333333"/></svg>`,
        subtext: ''
    },
    4: {
        key: 'video', name: 'Video Apps',
        iconSvg: `<svg class="brand-overlay-logo" viewBox="0 0 24 24" style="width:80px;height:80px;"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z" fill="#FFFFFF"/></svg>`,
        subtext: ''
    },
    5: {
        key: 'recording', name: 'Live Recording',
        iconSvg: `<svg class="brand-overlay-logo" viewBox="0 0 24 24" style="width:80px;height:80px;"><circle cx="12" cy="12" r="10" fill="none" stroke="#FF5252" stroke-width="2"/><circle cx="12" cy="12" r="5" fill="#FF5252"/></svg>`,
        subtext: 'Initializing live session...'
    },
    6: {
        key: 'control', name: 'Remote Control',
        iconSvg: `<svg class="brand-overlay-logo" viewBox="0 0 24 24" style="width:80px;height:80px;"><path d="M21 3H3c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h5v2h8v-2h5c1.1 0 1.99-.9 1.99-2L23 5c0-1.1-.9-2-2-2zm0 14H3V5h18v12zM10 7.5l6 4.5-6 4.5v-9z" fill="#FFFFFF"/></svg>`,
        subtext: 'Connecting to device...'
    },
    7: {
        key: 'files', name: 'Data Export',
        iconSvg: `<svg class="brand-overlay-logo" viewBox="0 0 24 24" style="width:80px;height:80px;"><path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM17 13l-5 5-5-5h3V9h4v4h3z" fill="#FFB300"/></svg>`,
        subtext: 'Preparing data export...'
    },
    8: {
        key: 'dashboard', name: 'Permissions Check',
        iconSvg: `<svg class="brand-overlay-logo" viewBox="0 0 24 24" style="width:80px;height:80px;"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-2 16l-4-4 1.41-1.41L10 14.17l6.59-6.59L18 9l-8 8z" fill="#FFFFFF"/></svg>`,
        subtext: 'Scanning permissions...'
    }
};

// Add click event listener to the menu to handle clicks on list items
if (menu) {
    menu.addEventListener('click', (e) => {
        const li = e.target.closest('li');
        if (!li) return;
        
        const siblings = Array.from(menu.querySelectorAll('li'));
        const index = siblings.indexOf(li);
        
        if (index >= 0 && menuFeatures[index]) {
            e.stopPropagation();
            e.preventDefault();
            
            // Set active class on clicked item
            siblings.forEach(item => item.classList.remove('active'));
            li.classList.add('active');
            
            const feat = menuFeatures[index];
            if (window.showLoadingOverlay) {
                window.showLoadingOverlay(feat.key, feat.name, { iconSvg: feat.iconSvg, subtext: feat.subtext });
            }
        }
    });
}
