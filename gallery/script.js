// // js/script.js
// document.addEventListener('DOMContentLoaded', () => {
//     const gallery = document.getElementById('gallery-images');
//     const title = document.getElementById('gallery-title');
//     const urlParams = new URLSearchParams(window.location.search);
//     const event = urlParams.get('event');
  
//     // Image data for events
//     const eventImages = {
//       'annual-day-2025': [
//         { src: '../assets/images/gallery/annualday/2025/1.jpg', alt: 'Annual Day 2025 Image 1' },
//         { src: '../assets/images/gallery/annualday/2025/2.jpg', alt: 'Annual Day 2025 Image 2' },
//         { src: '../assets/images/gallery/annualday/2025/3.jpg', alt: 'Annual Day 2025 Image 3' },
//       ],
//       'sports-day-2025': [
//         { src: '../assets/images/gallery/sports-day1.jpg', alt: 'Sports Day 2025 Image 1' },
//         { src: '../assets/images/gallery/sports-day2.jpg', alt: 'Sports Day 2025 Image 2' },
//       ],
//       'freshers-day-2024': [
//         { src: '../assets/images/gallery/freshers-day1.jpg', alt: 'Freshers Day 2024 Image 1' },
//         { src: '../assets/images/gallery/freshers-day2.jpg', alt: 'Freshers Day 2024 Image 2' },
//       ],
//       'farewell-2025': [
//         { src: '../assets/images/gallery/farewell1.jpg', alt: 'Farewell 2025 Image 1' },
//         { src: '../assets/images/gallery/farewell2.jpg', alt: 'Farewell 2025 Image 2' },
//       ],
//       'national-confest-2025': [
//         { src: '../assets/images/gallery/confest1.jpg', alt: 'National Confest 2025 Image 1' },
//         { src: '../assets/images/gallery/confest2.jpg', alt: 'National Confest 2025 Image 2' },
//       ],
//     };
  
//     // Update title and images based on event
//     if (event && eventImages[event]) {
//       title.textContent = event.replace(/-/g, ' ').replace(/(^\w|\s\w)/g, c => c.toUpperCase()) + ' Gallery';
//       eventImages[event].forEach(img => {
//         const imgElement = document.createElement('img');
//         imgElement.src = img.src;
//         imgElement.alt = img.alt;
//         gallery.appendChild(imgElement);
//       });
//     } else {
//       title.textContent = 'Event Gallery';
//       const fallbackImg = document.createElement('img');
//       fallbackImg.src = '../assets/images/gallery/default-gallery.jpg';
//       fallbackImg.alt = 'Default Gallery Image';
//       gallery.appendChild(fallbackImg);
//     }
//   });
//   document.addEventListener('DOMContentLoaded', () => {
//     const gallery = document.getElementById('gallery-images');
//     const title = document.getElementById('gallery-title');
//     const urlParams = new URLSearchParams(window.location.search);
//     const event = urlParams.get('event');
//     console.log('Event:', event); // Debug
//     console.log('Gallery element:', gallery); // Debug
//     console.log('Event images:', eventImages[event]); // Debug

//     if (event && eventImages[event]) {
//         title.textContent = event.replace(/-/g, ' ').replace(/(^\w|\s\w)/g, c => c.toUpperCase()) + ' Gallery';
//         eventImages[event].forEach(img => {
//             console.log('Adding image:', img.src); // Debug
//             const imgElement = document.createElement('img');
//             imgElement.src = img.src;
//             imgElement.alt = img.alt;
//             gallery.appendChild(imgElement);
//         });
//     } else {
//         console.log('Loading fallback image'); // Debug
//         title.textContent = 'Event Gallery';
//         const fallbackImg = document.createElement('img');
//         fallbackImg.src = '../assets/images/gallery/default-gallery.jpg';
//         fallbackImg.alt = 'Default Gallery Image';
//         gallery.appendChild(fallbackImg);
//     }
// });




document.addEventListener('DOMContentLoaded', () => {
    const gallery = document.getElementById('gallery-images');
    const title = document.getElementById('gallery-title');
    const urlParams = new URLSearchParams(window.location.search);
    const event = urlParams.get('event');

    // Image data for events with programs
    const eventImages = {
        'annual-day-2025': [
            // Day 1: Program 1 - Opening Ceremony
            { type: 'title', text: 'Annual Day Celebration' },
            { type: 'image', src: '../assets/images/gallery/annualday/2025/a1.jpg', alt: 'Annual Day 2025 Day 1 Opening 1' },
            { type: 'image', src: '../assets/images/gallery/annualday/2025/a2.jpg', alt: 'Annual Day 2025 Day 1 Opening 2' },
            { type: 'image', src: '../assets/images/gallery/annualday/2025/a3.jpg', alt: 'Annual Day 2025 Day 1 Opening 1' },
            { type: 'image', src: '../assets/images/gallery/annualday/2025/a4.jpg', alt: 'Annual Day 2025 Day 1 Opening 2' },
            { type: 'image', src: '../assets/images/gallery/annualday/2025/a5.jpg', alt: 'Annual Day 2025 Day 1 Opening 1' },
            { type: 'image', src: '../assets/images/gallery/annualday/2025/a6.jpg', alt: 'Annual Day 2025 Day 1 Opening 2' },
            { type: 'image', src: '../assets/images/gallery/annualday/2025/a7.jpg', alt: 'Annual Day 2025 Day 1 Opening 1' },
            { type: 'image', src: '../assets/images/gallery/annualday/2025/a8.jpg', alt: 'Annual Day 2025 Day 1 Opening 2' },
            { type: 'image', src: '../assets/images/gallery/annualday/2025/a9.jpg', alt: 'Annual Day 2025 Day 1 Opening 2' },

            { type: 'title', text: 'Yakshagana – Hidimba Vivaha performed by the students of MGM Evening College' },
            { type: 'image', src: '../assets/images/gallery/annualday/2025/y1.jpg', alt: 'Annual Day 2025 Day 1 Opening 1' },
            { type: 'image', src: '../assets/images/gallery/annualday/2025/y2.jpg', alt: 'Annual Day 2025 Day 1 Opening 2' },
            { type: 'image', src: '../assets/images/gallery/annualday/2025/y3.jpg', alt: 'Annual Day 2025 Day 1 Opening 1' },
            { type: 'image', src: '../assets/images/gallery/annualday/2025/y4.jpg', alt: 'Annual Day 2025 Day 1 Opening 2' },
            { type: 'image', src: '../assets/images/gallery/annualday/2025/y5.jpg', alt: 'Annual Day 2025 Day 1 Opening 1' },
            { type: 'image', src: '../assets/images/gallery/annualday/2025/y6.jpg', alt: 'Annual Day 2025 Day 1 Opening 2' },

            { type: 'title', text: 'SWC day Celebration' },
            { type: 'image', src: '../assets/images/gallery/annualday/2025/s1.jpg', alt: 'Annual Day 2025 Day 1 Opening 1' },
            { type: 'image', src: '../assets/images/gallery/annualday/2025/s2.jpg', alt: 'Annual Day 2025 Day 1 Opening 2' },
            { type: 'image', src: '../assets/images/gallery/annualday/2025/s3.jpg', alt: 'Annual Day 2025 Day 1 Opening 1' },
            { type: 'image', src: '../assets/images/gallery/annualday/2025/s5.jpg', alt: 'Annual Day 2025 Day 1 Opening 2' },
            { type: 'image', src: '../assets/images/gallery/annualday/2025/s6.jpg', alt: 'Annual Day 2025 Day 1 Opening 1' },
            { type: 'image', src: '../assets/images/gallery/annualday/2025/s7.jpg', alt: 'Annual Day 2025 Day 1 Opening 2' },
            { type: 'image', src: '../assets/images/gallery/annualday/2025/s4.jpg', alt: 'Annual Day 2025 Day 1 Opening 2' },

            { type: 'title', text: 'Urvi (ಉರ್ವಿ) – a bold and thought-provoking drama by the students of MGM Evening College' },
            { type: 'image', src: '../assets/images/gallery/annualday/2025/u1.jpg', alt: 'Annual Day 2025 Day 1 Opening 1' },
            { type: 'image', src: '../assets/images/gallery/annualday/2025/u2.jpg', alt: 'Annual Day 2025 Day 1 Opening 2' },
            { type: 'image', src: '../assets/images/gallery/annualday/2025/u3.jpg', alt: 'Annual Day 2025 Day 1 Opening 1' },
            { type: 'image', src: '../assets/images/gallery/annualday/2025/u4.jpg', alt: 'Annual Day 2025 Day 1 Opening 2' },
            { type: 'image', src: '../assets/images/gallery/annualday/2025/u5.jpg', alt: 'Annual Day 2025 Day 1 Opening 1' },
            { type: 'image', src: '../assets/images/gallery/annualday/2025/u6.jpg', alt: 'Annual Day 2025 Day 1 Opening 2' },
            { type: 'image', src: '../assets/images/gallery/annualday/2025/u7.jpg', alt: 'Annual Day 2025 Day 1 Opening 1' },
            { type: 'image', src: '../assets/images/gallery/annualday/2025/u8.jpg', alt: 'Annual Day 2025 Day 1 Opening 2' },

            { type: 'title', text: 'Performance by students' },
            { type: 'image', src: '../assets/images/gallery/annualday/2025/p1.jpg', alt: 'Annual Day 2025 Day 1 Opening 1' },
            { type: 'image', src: '../assets/images/gallery/annualday/2025/p2.jpg', alt: 'Annual Day 2025 Day 1 Opening 2' },
            { type: 'image', src: '../assets/images/gallery/annualday/2025/p3.jpg', alt: 'Annual Day 2025 Day 1 Opening 1' },
            { type: 'image', src: '../assets/images/gallery/annualday/2025/p4.jpg', alt: 'Annual Day 2025 Day 1 Opening 2' },
            { type: 'image', src: '../assets/images/gallery/annualday/2025/p5.jpg', alt: 'Annual Day 2025 Day 1 Opening 1' },
            { type: 'image', src: '../assets/images/gallery/annualday/2025/p6.jpg', alt: 'Annual Day 2025 Day 1 Opening 2' },
        ],
        'sports-day-2025': [
            { type: 'image', src: '../assets/images/gallery/sports day/2025/z1.jpg', alt: 'Annual Day 2025 Day 1 Opening 1' },
            { type: 'image', src: '../assets/images/gallery/sports day/2025/z2.jpg', alt: 'Annual Day 2025 Day 1 Opening 2' },
            { type: 'image', src: '../assets/images/gallery/sports day/2025/z3.jpg', alt: 'Annual Day 2025 Day 1 Opening 1' },
            { type: 'image', src: '../assets/images/gallery/sports day/2025/z4.jpg', alt: 'Annual Day 2025 Day 1 Opening 2' },
            { type: 'image', src: '../assets/images/gallery/sports day/2025/z5.jpg', alt: 'Annual Day 2025 Day 1 Opening 1' },
            { type: 'image', src: '../assets/images/gallery/sports day/2025/z6.jpg', alt: 'Annual Day 2025 Day 1 Opening 2' },
            { type: 'image', src: '../assets/images/gallery/sports day/2025/z7.jpg', alt: 'Annual Day 2025 Day 1 Opening 2' },
            { type: 'image', src: '../assets/images/gallery/sports day/2025/z8.jpg', alt: 'Annual Day 2025 Day 1 Opening 1' },
            { type: 'image', src: '../assets/images/gallery/sports day/2025/z9.jpg', alt: 'Annual Day 2025 Day 1 Opening 2' },
         
        ],
        'freshers-day-2024': [
            { type: 'image', src: '../assets/images/gallery/freshers-day1.jpg', alt: 'Freshers Day 2024 Image 1' },
            { type: 'image', src: '../assets/images/gallery/freshers-day2.jpg', alt: 'Freshers Day 2024 Image 2' },
            { type: 'title', text: 'Welcome Session' },
        ],
        'farewell-2025': [
            { type: 'image', src: '../assets/images/gallery/farewell1.jpg', alt: 'Farewell 2025 Image 1' },
            { type: 'image', src: '../assets/images/gallery/farewell2.jpg', alt: 'Farewell 2025 Image 2' },
            { type: 'title', text: 'Farewell Ceremony' },
        ],
        'national-confest-2025': [
            { type: 'title', text: 'National ConFest 2025 – Entrepreneurship in the Digital Age: Thriving Business Amidst Digital Disruption' },
            { type: 'image', src: '../assets/images/gallery/confest/c1.jpg', alt: 'National Confest 2025 Image 1' },
            { type: 'image', src: '../assets/images/gallery/confest/c2.jpg', alt: 'National Confest 2025 Image 2' },
            { type: 'image', src: '../assets/images/gallery/confest/c3.jpg', alt: 'National Confest 2025 Image 2' },
            { type: 'image', src: '../assets/images/gallery/confest/c4.jpg', alt: 'National Confest 2025 Image 2' },
            { type: 'image', src: '../assets/images/gallery/confest/c5.jpg', alt: 'National Confest 2025 Image 2' },
            { type: 'image', src: '../assets/images/gallery/confest/c6.jpg', alt: 'National Confest 2025 Image 2' },
            { type: 'image', src: '../assets/images/gallery/confest/c7.jpg', alt: 'National Confest 2025 Image 2' },
            { type: 'image', src: '../assets/images/gallery/confest/c8.jpg', alt: 'National Confest 2025 Image 2' },
            { type: 'image', src: '../assets/images/gallery/confest/c9.jpg', alt: 'National Confest 2025 Image 2' },
            { type: 'image', src: '../assets/images/gallery/confest/c10.jpg', alt: 'National Confest 2025 Image 1' },
            { type: 'image', src: '../assets/images/gallery/confest/c11.jpg', alt: 'National Confest 2025 Image 2' },
            { type: 'image', src: '../assets/images/gallery/confest/c12.jpg', alt: 'National Confest 2025 Image 2' },
            { type: 'image', src: '../assets/images/gallery/confest/c13.jpg', alt: 'National Confest 2025 Image 2' },
            { type: 'image', src: '../assets/images/gallery/confest/c14.jpg', alt: 'National Confest 2025 Image 2' },
            { type: 'image', src: '../assets/images/gallery/confest/c15.jpg', alt: 'National Confest 2025 Image 2' },
            { type: 'image', src: '../assets/images/gallery/confest/c16.jpg', alt: 'National Confest 2025 Image 2' },
            { type: 'image', src: '../assets/images/gallery/confest/c17.jpg', alt: 'National Confest 2025 Image 2' },
            { type: 'image', src: '../assets/images/gallery/confest/c18.jpg', alt: 'National Confest 2025 Image 2' },
            { type: 'image', src: '../assets/images/gallery/confest/c19.jpg', alt: 'National Confest 2025 Image 2' },
            { type: 'image', src: '../assets/images/gallery/confest/c20.jpg', alt: 'National Confest 2025 Image 2' },
            { type: 'image', src: '../assets/images/gallery/confest/c21.jpg', alt: 'National Confest 2025 Image 2' },
            { type: 'image', src: '../assets/images/gallery/confest/c22.jpg', alt: 'National Confest 2025 Image 2' },
            { type: 'image', src: '../assets/images/gallery/confest/c23.jpg', alt: 'National Confest 2025 Image 2' },
            { type: 'image', src: '../assets/images/gallery/confest/c24.jpg', alt: 'National Confest 2025 Image 2' },
            { type: 'image', src: '../assets/images/gallery/confest/c25.jpg', alt: 'National Confest 2025 Image 2' },
            { type: 'image', src: '../assets/images/gallery/confest/c26.jpg', alt: 'National Confest 2025 Image 2' },
            { type: 'image', src: '../assets/images/gallery/confest/c27.jpg', alt: 'National Confest 2025 Image 2' },
            { type: 'image', src: '../assets/images/gallery/confest/c28.jpg', alt: 'National Confest 2025 Image 2' },
            { type: 'image', src: '../assets/images/gallery/confest/c29.jpg', alt: 'National Confest 2025 Image 2' },
            { type: 'image', src: '../assets/images/gallery/confest/c30.jpg', alt: 'National Confest 2025 Image 2' }
        
        ],
        'pta-meeting-2025': [
            { type: 'title', text: 'PTA Meeting 2025' },
            { type: 'image', src: '../assets/images/gallery/ptameeting/p1.jpg', alt: 'PTA Meeting 2025 Image 1' },
            { type: 'image', src: '../assets/images/gallery/ptameeting/p2.jpg', alt: 'PTA Meeting 2025 Image 2' },
            { type: 'image', src: '../assets/images/gallery/ptameeting/p3.jpg', alt: 'PTA Meeting 2025 Image 1' },
            { type: 'image', src: '../assets/images/gallery/ptameeting/p4.jpg', alt: 'PTA Meeting 2025 Image 2' },
            { type: 'image', src: '../assets/images/gallery/ptameeting/p5.jpg', alt: 'PTA Meeting 2025 Image 2' },
            { type: 'image', src: '../assets/images/gallery/ptameeting/p6.jpg', alt: 'PTA Meeting 2025 Image 2' },
            { type: 'image', src: '../assets/images/gallery/ptameeting/p7.jpg', alt: 'PTA Meeting 2025 Image 2' },
            { type: 'image', src: '../assets/images/gallery/ptameeting/p8.jpg', alt: 'PTA Meeting 2025 Image 2' },
            { type: 'image', src: '../assets/images/gallery/ptameeting/p9.jpg', alt: 'PTA Meeting 2025 Image 2' },
            { type: 'image', src: '../assets/images/gallery/ptameeting/p10.jpg', alt: 'PTA Meeting 2025 Image 2' },
            { type: 'image', src: '../assets/images/gallery/ptameeting/p11.jpg', alt: 'PTA Meeting 2025 Image 2' },
            { type: 'image', src: '../assets/images/gallery/ptameeting/p12.jpg', alt: 'PTA Meeting 2025 Image 2' },
            { type: 'image', src: '../assets/images/gallery/ptameeting/p13.jpg', alt: 'PTA Meeting 2025 Image 2' },
            { type: 'image', src: '../assets/images/gallery/ptameeting/p14.jpg', alt: 'PTA Meeting 2025 Image 2' },
            { type: 'image', src: '../assets/images/gallery/ptameeting/p15.jpg', alt: 'PTA Meeting 2025 Image 2' },
            { type: 'image', src: '../assets/images/gallery/ptameeting/p16.jpg', alt: 'PTA Meeting 2025 Image 2' },
            { type: 'image', src: '../assets/images/gallery/ptameeting/p17.jpg', alt: 'PTA Meeting 2025 Image 2' },
            { type: 'image', src: '../assets/images/gallery/ptameeting/p18.jpg', alt: 'PTA Meeting 2025 Image 2' },
            { type: 'image', src: '../assets/images/gallery/ptameeting/p19.jpg', alt: 'PTA Meeting 2025 Image 2' },
            { type: 'image', src: '../assets/images/gallery/ptameeting/p20.jpg', alt: 'PTA Meeting 2025 Image 2' },
            { type: 'image', src: '../assets/images/gallery/ptameeting/p21.jpg', alt: 'PTA Meeting 2025 Image 2' },
            
        ],
         'soft-skills-workshop-2025': [
            { type: 'title', text: 'Soft Skills Workshop 2025 - Fluent and Fearless ' },
            { type: 'image', src: '../assets/images/gallery/softskills/sf1 (3).jpg', alt: 'Soft Skills Workshop 2025 Image 2' },
            { type: 'image', src: '../assets/images/gallery/softskills/sf1 (4).jpg', alt: 'Soft Skills Workshop 2025 Image 2' },
            { type: 'image', src: '../assets/images/gallery/softskills/sf1 (5).jpg', alt: 'Soft Skills Workshop 2025 Image 1' },
            { type: 'image', src: '../assets/images/gallery/softskills/sf1 (6).jpg', alt: 'Soft Skills Workshop 2025 Image 2' },
            { type: 'image', src: '../assets/images/gallery/softskills/sf1 (7).jpg', alt: 'Soft Skills Workshop 2025 Image 2' },
            { type: 'image', src: '../assets/images/gallery/softskills/sf1 (8).jpg', alt: 'Soft Skills Workshop 2025 Image 2' },
            { type: 'image', src: '../assets/images/gallery/softskills/sf1 (9).jpg', alt: 'Soft Skills Workshop 2025 Image 1' },
            { type: 'image', src: '../assets/images/gallery/softskills/sf1 (2).jpg', alt: 'Soft Skills Workshop 2025 Image 2' },
            { type: 'image', src: '../assets/images/gallery/softskills/sf1 (10).jpg', alt: 'Soft Skills Workshop 2025 Image 2' },
            { type: 'image', src: '../assets/images/gallery/softskills/sf1 (11).jpg', alt: 'Soft Skills Workshop 2025 Image 2' },
            { type: 'image', src: '../assets/images/gallery/softskills/sf1 (12).jpg', alt: 'Soft Skills Workshop 2025 Image 2' },
            { type: 'image', src: '../assets/images/gallery/softskills/sf1 (13).jpg', alt: 'Soft Skills Workshop 2025 Image 1' },
            { type: 'image', src: '../assets/images/gallery/softskills/sf1 (14).jpg', alt: 'Soft Skills Workshop 2025 Image 2' },
            { type: 'image', src: '../assets/images/gallery/softskills/sf1 (15).jpg', alt: 'Soft Skills Workshop 2025 Image 2' },
            { type: 'image', src: '../assets/images/gallery/softskills/sf1 (16).jpg', alt: 'Soft Skills Workshop 2025 Image 2' },
            { type: 'image', src: '../assets/images/gallery/softskills/sf1 (17).jpg', alt: 'Soft Skills Workshop 2025 Image 1' },
            { type: 'image', src: '../assets/images/gallery/softskills/sf1 (18).jpg', alt: 'Soft Skills Workshop 2025 Image 2' },
            { type: 'image', src: '../assets/images/gallery/softskills/sf1 (1).jpg', alt: 'Soft Skills Workshop 2025 Image 1' },
            { type: 'image', src: '../assets/images/gallery/softskills/sf1 (19).jpg', alt: 'Soft Skills Workshop 2025 Image 2' }

         ]
    };

    // Update title and images based on event
    if (event && eventImages[event]) {
        title.textContent = event.replace(/-/g, ' ').replace(/(^\w|\s\w)/g, c => c.toUpperCase()) + ' Gallery';
        eventImages[event].forEach(item => {
            if (item.type === 'image') {
                const imgElement = document.createElement('img');
                imgElement.src = item.src;
                imgElement.alt = item.alt;
                gallery.appendChild(imgElement);
            } else if (item.type === 'title') {
                const titleElement = document.createElement('h2');
                titleElement.textContent = item.text;
                titleElement.className = 'gallery-image-title';
                gallery.appendChild(titleElement);
            }
        });
    } else {
        title.textContent = 'Event Gallery';
        const fallbackImg = document.createElement('img');
        fallbackImg.src = '../assets/images/gallery/default-gallery.jpg';
        fallbackImg.alt = 'Default Gallery Image';
        gallery.appendChild(fallbackImg);
        const fallbackTitle = document.createElement('h2');
        fallbackTitle.textContent = 'Default Gallery';
        fallbackTitle.className = 'gallery-image-title';
        gallery.appendChild(fallbackTitle);
    }
});