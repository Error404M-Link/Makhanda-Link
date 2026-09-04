const faqs = document.querySelectorAll(".faq"); 

faqs.forEach(faq =>{
    const answer = faq.querySelector(".answer");
    const arrow = faq.querySelector(".svg");

    answer.style.display = "none"; 

    faq.addEventListener("click", () => {
        if(answer.style.display ==="none"){
            answer.style.display = "block"; 
            arrow.style.transform = "rotate(180deg)"; 
        }else{
            answer.style.display = "none"; 
            arrow.style.transform = "rotate(0deg)"; 
        }
    });
});    