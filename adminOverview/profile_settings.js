document.addEventListener("DOMContentLoaded", () => {
    // DOM Elements
    const personalInfoSection = document.getElementById("personalInfoSection");
    const passwordSection = document.getElementById("passwordSection");
    const navPersonalInfo = document.getElementById("navPersonalInfo");
    const navPassword = document.getElementById("navPassword");

    const profileForm = document.getElementById("profileForm");
    const passwordForm = document.getElementById("passwordForm");
    const imageUploadInput = document.getElementById("imageUpload");
    const profileImage = document.getElementById("profileImage");
    
    const btnTriggerModal = document.getElementById("btnTriggerModal");
    const btnTriggerPasswordModal = document.getElementById("btnTriggerPasswordModal");
    const modal = document.getElementById("passwordModal");
    const modalConfirmBtn = document.getElementById("btnConfirmModal");
    const modalCancelBtn = document.getElementById("btnCancelModal");
    const modalPasswordInput = document.getElementById("modalConfirmPassword");
    const modalError = document.getElementById("modalError");
    const confirmPasswordInput = document.getElementById("confirmPasswordInput");
    const confirmPasswordInputPwd = document.getElementById("confirmPasswordInputPwd");

    // KEEP TRACK OF WHICH POPUP TO USE ONCE CONFIRMED => PROFILE OR PASSWORD
    let pendingAction = null;

    // CACHE INITIAL FORM VALUES BEFORE DISCARDING THE CHANGES
    const originalImageSrc = profileImage ? profileImage.src : "";
    const initialFormData = {
        firstName: document.getElementById("first_Name") ? document.getElementById("first_Name").value : "",
        surname: document.getElementById("surname") ? document.getElementById("surname").value : "",
        address: document.getElementById("address") ? document.getElementById("address").value : "",
        contact: document.getElementById("contact") ? document.getElementById("contact").value : "",
        ward_id: document.getElementById("ward_id") ? document.getElementById("ward_id").value : ""
    };

    // TAB NAVIGATION SWITCHING
    if (navPersonalInfo && navPassword) {
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
    }

    // REVIEW PROFILE PICTURE AND SAVE WITHOUT PASSWORD CONFIRMATION
    const profileImageForm = document.getElementById("profileImageForm");
    const imageUploadActions = document.getElementById("imageUploadActions");
    const btnCancelImage = document.getElementById("btnCancelImage");

    if (imageUploadInput && profileImage) {
        // UPLOAD AND READ THE IMAGE.
        imageUploadInput.addEventListener("change", (e) => {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (event) => { 
                    profileImage.src = event.target.result;
                };
                reader.readAsDataURL(file);

                if (imageUploadActions) imageUploadActions.classList.remove("hiddenSection");
            }
        });
    }

    if (btnCancelImage) {
        // CANCEL THE UPDATE
        btnCancelImage.addEventListener("click", () => {
            if (imageUploadInput) imageUploadInput.value = "";
            if (profileImage) profileImage.src = originalImageSrc;
            if (imageUploadActions) imageUploadActions.classList.add("hiddenSection");
        });
    }

    // DISCARD CHANGES
    const btnDiscardProfile = document.getElementById("btnDiscardProfile");
    if (btnDiscardProfile) {
        btnDiscardProfile.addEventListener("click", () => {
            if (document.getElementById("first_Name")) document.getElementById("first_Name").value = initialFormData.firstName;
            if (document.getElementById("surname")) document.getElementById("surname").value = initialFormData.surname;
            if (document.getElementById("address")) document.getElementById("address").value = initialFormData.address;
            if (document.getElementById("contact")) document.getElementById("contact").value = initialFormData.contact;
            if (document.getElementById("ward_id")) document.getElementById("ward_id").value = initialFormData.ward_id;
            if (profileImage) profileImage.src = originalImageSrc;
            if (imageUploadInput) imageUploadInput.value = "";
        });
    }

    const btnDiscardPassword = document.getElementById("btnDiscardPassword");
    if (btnDiscardPassword && passwordForm) {
        btnDiscardPassword.addEventListener("click", () => {
            passwordForm.reset();
        });
    }

    // SHOW CONFIRMATION => PROFILE SAVE
    if (btnTriggerModal && modal) {
        btnTriggerModal.addEventListener("click", (e) => {
            e.preventDefault();
            pendingAction = "profile";
            if (modalPasswordInput) modalPasswordInput.value = "";
            if (modalError) modalError.classList.add("hiddenError");
            modal.classList.remove("hiddenModal");
            if (modalPasswordInput) modalPasswordInput.focus();
        });
    }

    // SHOW CONFIRMATION MODAL (PASSWORD UPDATE)
    if (btnTriggerPasswordModal && modal) {
        btnTriggerPasswordModal.addEventListener("click", (e) => {
            e.preventDefault();

            // CHECK IF THE NEW PASSWORDS MATCH
            const newPwd = document.getElementById("newPassword") ? document.getElementById("newPassword").value : "";
            const confirmPwd = document.getElementById("confirmNewPassword") ? document.getElementById("confirmNewPassword").value : "";
            if (newPwd !== confirmPwd) {
                alert("New passwords do not match.");
                return;
            }

            pendingAction = "password";
            if (modalPasswordInput) modalPasswordInput.value = "";
            if (modalError) modalError.classList.add("hiddenError");
            modal.classList.remove("hiddenModal");
            if (modalPasswordInput) modalPasswordInput.focus();
        });
    }

    // CLOSE MODAL (CANCEL BUTTON)
    if (modalCancelBtn && modal) {
        modalCancelBtn.addEventListener("click", () => {
            modal.classList.add("hiddenModal");
        });
    }

    // CLOSE MODAL (CLICK OUTSIDE OVERLAY)
    if (modal) {
        modal.addEventListener("click", (e) => {
            if (e.target === modal) {
                modal.classList.add("hiddenModal");
            }
        });
    }

    // CONFIRM PASSWORD & SUBMIT WHICHEVER FORM TRIGGERED THE MODAL
    if (modalConfirmBtn) {
        modalConfirmBtn.addEventListener("click", () => {
            const passwordValue = modalPasswordInput ? modalPasswordInput.value.trim() : "";
            
            if (!passwordValue) {
                if (modalError) modalError.classList.remove("hiddenError");
                return;
            }

            if (modal) {
                modal.classList.add("hiddenModal");
            }

            if (pendingAction === "profile") {
                if (confirmPasswordInput) confirmPasswordInput.value = passwordValue;
                if (profileForm) profileForm.submit();
            } else if (pendingAction === "password") {
                if (confirmPasswordInputPwd) confirmPasswordInputPwd.value = passwordValue;
                if (passwordForm) passwordForm.submit();
            }

            pendingAction = null;
        });
    }

    // ALLOW SUBMITTING MODAL WITH ENTER KEY
    if (modalPasswordInput) {
        modalPasswordInput.addEventListener("keypress", (e) => {
            if (e.key === "Enter") {
                e.preventDefault();
                if (modalConfirmBtn) modalConfirmBtn.click();
            }
        });
    }

    // CHANGE PASSWORD TAB FORM VALIDATION
    if (passwordForm) {
        passwordForm.addEventListener("submit", (e) => {
            const newPwd = document.getElementById("newPassword") ? document.getElementById("newPassword").value : "";
            const confirmPwd = document.getElementById("confirmNewPassword") ? document.getElementById("confirmNewPassword").value : "";

            if (newPwd !== confirmPwd) {
                e.preventDefault();
                alert("New passwords do not match.");
            }
        });
    }
});