document.addEventListener("DOMContentLoaded", () => {
    // Elements
    const personalInfoSection = document.getElementById("personalInfoSection");
    const passwordSection = document.getElementById("passwordSection");
    const navPersonalInfo = document.getElementById("navPersonalInfo");
    const navPassword = document.getElementById("navPassword");

    const profileForm = document.getElementById("profileForm");
    const passwordForm = document.getElementById("passwordForm");
    const imageUploadInput = document.getElementById("imageUpload");
    const profileImage = document.getElementById("profileImage");
    
    const btnTriggerModal = document.getElementById("btnTriggerModal");
    const modal = document.getElementById("passwordModal");
    const modalConfirmBtn = document.getElementById("btnConfirmModal");
    const modalCancelBtn = document.getElementById("btnCancelModal");
    const modalPasswordInput = document.getElementById("modalConfirmPassword");
    const modalError = document.getElementById("modalError");
    const confirmPasswordInput = document.getElementById("confirmPasswordInput");

    // Cache initial form values for Discard functionality
    const originalImageSrc = profileImage.src;
    const initialFormData = {
        firstName: document.getElementById("first_Name").value,
        surname: document.getElementById("surname").value,
        address: document.getElementById("address").value,
        contact: document.getElementById("contact").value,
        ward_id: document.getElementById("ward_id").value
    };

    // 1. TAB NAVIGATION SWITCHING
    navPersonalInfo.addEventListener("click", (e) => {
        e.preventDefault();
        navPersonalInfo.classList.add("active");
        navPassword.classList.remove("active");
        personalInfoSection.classList.remove("hiddenSection");
        passwordSection.classList.add("hiddenSection");
    });

    navPassword.addEventListener("click", (e) => {
        e.preventDefault();
        navPassword.classList.add("active");
        navPersonalInfo.classList.remove("active");
        passwordSection.classList.remove("hiddenSection");
        personalInfoSection.classList.add("hiddenSection");
    });

    // 2. PROFILE IMAGE INSTANT PREVIEW
    imageUploadInput.addEventListener("change", (e) => {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = (event) => {
                profileImage.src = event.target.result;
            };
            reader.readAsDataURL(file);
        }
    });

    // 3. DISCARD CHANGES
    document.getElementById("btnDiscardProfile").addEventListener("click", () => {
        document.getElementById("first_Name").value = initialFormData.firstName;
        document.getElementById("surname").value = initialFormData.surname;
        document.getElementById("address").value = initialFormData.address;
        document.getElementById("contact").value = initialFormData.contact;
        document.getElementById("ward_id").value = initialFormData.ward_id;
        
        profileImage.src = originalImageSrc;
        imageUploadInput.value = "";
    });

    document.getElementById("btnDiscardPassword").addEventListener("click", () => {
        passwordForm.reset();
    });

    // 4. SHOW CONFIRMATION MODAL
    btnTriggerModal.addEventListener("click", () => {
        modalPasswordInput.value = "";
        modalError.classList.add("hiddenError");
        modal.classList.remove("hiddenModal");
    });

    modalCancelBtn.addEventListener("click", () => {
        modal.classList.add("hiddenModal");
    });

    // 5. CONFIRM PASSWORD & SUBMIT PROFILE FORM
    modalConfirmBtn.addEventListener("click", () => {
        const passwordValue = modalPasswordInput.value.trim();
        if (!passwordValue) {
            modalError.classList.remove("hiddenError");
            return;
        }

        // Attach password to hidden form input and submit traditionally
        confirmPasswordInput.value = passwordValue;
        modal.classList.add("hiddenModal");
        profileForm.submit();
    });

    // 6. PASSWORD FORM VALIDATION BEFORE SUBMISSION
    passwordForm.addEventListener("submit", (e) => {
        const newPwd = document.getElementById("newPassword").value;
        const confirmPwd = document.getElementById("confirmNewPassword").value;

        if (newPwd !== confirmPwd) {
            e.preventDefault();
            alert("New passwords do not match.");
        }
    });
});