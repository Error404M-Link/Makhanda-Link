
//###################### SEARCH AND FILTERING ###############################################

/*document.addEventListener("DOMContentLoaded", () => {
    const searchInput = document.getElementById('faultSeachInput');
    const categoryFilter = document.getElementById('categoryFilter');
    const statusFilter = document.getElementById('statusFilter');
    const resultsContainer = document.getElementById('faultResultsContainer');

    function triggerFilterSearch(){
        const queryParam = new URLSearchParams({
            search: searchInput.value,
            category: categoryFilter.value,
            status: statusFilter.value  
        });

        fetch(`publicfaultBoardMainLogic.php?${queryParam.toString()}`)
            .then(response => response.text())
            .then(htmlMarkup => {
                resultsContainer.innerHTML = htmlMarkup;
            })
            .catch(error => console.error("Error executing active filter update: ", error));
    }
                                                            // Event listener for handling real-time change
    searchInput.addEventListener('input', triggerFilterSearch);
    categoryFilter.addEventListener('change', triggerFilterSearch);
    statusFilter.addEventListener('change', triggerFilterSearch);
}); */

//###################### INTERACTIVE GRAHAMSTOWN/MAKHANDA MAP ###############################

                                        // Ensure that the map runs after window data layer match up
function initGrahamstownMap(ticketData){

                                        // define the bounds locking down view exclusively to Grahamstown/Makhanda
    const GHTBounds = L.latLngBounds(
        L.latLng(-33.3400, 26.4700),     // South West corner
        L.latLng(-33.2800, 26.5800),     // North East corner
    );

    // initialise map frame
    const map = L.map('grahamstownMap', {
        center: [-33.3106, 26.5256],    // Centered directly on Grahamstown CBD
        zoom: 15,                       // Close street range scale
        minZoom: 10,                    // Outer zoom lock limit
        maxZoom: 18,                    // Inner zoom limit
        maxBounds: GHTBounds,           // Apply spatial box constraint
        maxBoundsViscosity: 1.0,        // Hard bounce containment behavior


                                        // LOCK INTERACTION INITIALLY UNTIL WHEN CLICKED 

        dragging: false,
        scrollWheelZoom: false,
        doubleClickZoom: false,
        boxZoom: false,
        touchZoom: false,
        keyboard: false
    });

                                        // Display OpenStreetMap graphics surface tiles
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

                                        // INTERACTIVITY ENGINE 

    const mapContainer = document.getElementById('grahamstownMap');
    mapContainer.style.cursor = 'pointer';

    function activateMap(){
        map.dragging.enable();
        map.scrollWheelZoom.enable();
        map.doubleClickZoom.enable();
        map.boxZoom.enable();
        map.touchZoom.enable();
        map.keyboard.enable();
        mapContainer.style.cursor = 'default';
    }
                                        // Listen to the activation click
    mapContainer.addEventListener('click', function(){
        activateMap();
    }, { once:true});

                                        //plot dynamic geograpgical pins
    
    ticketData.forEach(ticket=>{
        if(ticket.latitude && ticket.longitude){

            const popupBody = `
                <div style="font-family: sans-serif; min-width: 160px;">
                    <h4 style="margin: 0 0 5px 0; color: #111;">Ticket #${ticket.ticketId}</h4>
                    <p style="margin: 0 0 4px 0; font-size: 13px;"><strong>Category:</strong> ${ticket.category}</p>
                    <p style="margin: 0 0 4px 0; font-size: 13px;"><strong>Street:</strong> ${ticket.streetName}</p>
                    <p style="margin: 0 0 10px 0; font-size: 13px;"><strong>Status:</strong> <span style="color:#fd7e14; font-weight:bold;">${ticket.status}</span></p>
                    <a href="view_ticket.php?id=${ticket.ticketId}" style="display: block; font-size: 12px; color: #007bff; text-decoration: none; font-weight: bold;">Track Lifecycle →</a>
                </div>
            `;

            L.marker([parseFloat(ticket.latitude), parseFloat(ticket.longitude)]).addTo(map).bindPopup(popupBody);
        }
    });

}

// ################################ STATS ANIMATION ############################################
                                                    // making the number count every time they appear on screen
document.addEventListener("DOMContentLoaded", ()=> {
    const reels = document.querySelectorAll(".statReel");

    // setup the numbers inside the reels immediately on load
    reels.forEach((reel) => {
        const target = parseInt(reel.getAttribute('data-target'), 10);

        if(target==0){
            return;
        }

                                            // populate or start counting
        for(let i = 1; i <=target; i++){
            const numSpan = document.createElement('span');
            numSpan.textContent = i;
            reel.appendChild(numSpan);
        }

       /* setTimeout(() => {
            const height = reel.parentElement.clientHeight;
            reel.style.transform = `translateY(-${height * target}px)`;
        }, 300); */
    });

                            // Create the intersection observer engine
    const observerOptions = {
        root: null,         // use the browser viewport
        threshold: 0.2      // Trigger count when 20% of the item is visible on screen
    };

    const statsObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            const reel = entry.target;
            const target = parseInt(reel.getAttribute('data-target'), 10);
            const height = reel.parentElement.clientHeight;

                            // when entering viewport
            if(entry.isIntersecting && target > 0){
                reel.style.transform = `translateY(-${height * target}px)`;
            }else{
                            // When scrolling away
                reel.style.transition = "none";
                reel.style.transform = "translateY(0px)";

                reel.offsetHeight;
                reel.style.transition = "transform 1.2s cubic-bezier(0.1, 1, 0.1, 1)";
            }
        });
    }, observerOptions);

                            // attach every observer to each individual stats reel element frame
    reels.forEach(reel => statsObserver.observe(reel));

});

// ######################### Fault cards #########################################

document.addEventListener("DOMContentLoaded", () => {
    // colour palette from m-link colour palette 
    const cardColors = [
        '#A38C7A', // Medium taupe / earth brown
        '#BC9E8B', // Lighter warm clay brown
        '#F7E9D4', // Creamy sand / light beige
        '#B5C29E'  // Soft sage green
    ];

    // select all fault card
    const faultCards = document.querySelectorAll('.faultCard');

    // loop each card and assign a random number
    faultCards.forEach(card => {
        const randomIndex = Math.floor(Math.random() * cardColors.length);
        const randomColor = cardColors[randomIndex]; // take out a random color from the colour palette
        
        card.style.backgroundColor = randomColor;

        // identify elements inside the card
        const title = card.querySelector('h3');
        const locationText = card.querySelector('.location');
        const viewBtn = card.querySelector('.viewBtn');

        // If it's the bright beige card, use dark text. Otherwise, use light text.
        if (randomColor === '#F7E9D4') {
            // Dark styling for the light cream card
            if (title) title.style.color = '#2B2520';
            if (locationText) locationText.style.color = '#544941';
            
            if (viewBtn) {
                viewBtn.style.color = '#2B2520';
                viewBtn.style.borderColor = 'rgba(43, 37, 32, 0.4)';
                viewBtn.style.backgroundColor = 'rgba(43, 37, 32, 0.05)';
                
                // Reset listeners to use dark hover effects
                viewBtn.onmouseenter = () => viewBtn.style.backgroundColor = 'rgba(43, 37, 32, 0.15)';
                viewBtn.onmouseleave = () => viewBtn.style.backgroundColor = 'rgba(43, 37, 32, 0.05)';
            }
        } else {
            // Light styling for the medium/darker cards
            if (title) title.style.color = '#FFFFFF';
            if (locationText) locationText.style.color = '#F0F0F0';
            
            if (viewBtn) {
                viewBtn.style.color = '#FFFFFF';
                viewBtn.style.borderColor = 'rgba(255, 255, 255, 0.4)';
                viewBtn.style.backgroundColor = 'rgba(255, 255, 255, 0.08)';
                
                // Reset listeners to use light hover effects
                viewBtn.onmouseenter = () => viewBtn.style.backgroundColor = 'rgba(255, 255, 255, 0.2)';
                viewBtn.onmouseleave = () => viewBtn.style.backgroundColor = 'rgba(255, 255, 255, 0.08)';
            }
        }
    });
});

// ######################### How it works section ################################

document.addEventListener("DOMContentLoaded", function(){
    const modal = document.getElementById("howItWorksWrapper");
    const openBtn = document.getElementById("openhowItWorksBtn");
    const closeBtn = document.querySelector(".closeHowItWorks");

    if(openBtn && modal){
        openBtn.addEventListener("click", function(){
            modal.classList.add("show-modal");
            document.body.classList.add("modal-open"); // lock page background scroll
        });
    }

    if(closeBtn && modal){
        closeBtn.addEventListener("click", function(){
            modal.classList.remove("show-modal");
            document.body.classList.remove("modal-open"); // restore background scroll
        });
    }

    window.addEventListener("click", function(event){
        if(event.target==modal){
            modal.classList.remove("show-modal");
            document.body.classList.remove("modal-open"); // remove background scroll
        }
    });
});