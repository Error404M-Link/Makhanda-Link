// Sample In-Memory Database representing M-Link Municipal Notices
let notices = [
    {
        id: 1,
        title: "Planned Water Maintenance - Ward 2",
        category: "Water",
        scope: "Ward 2",
        publishDate: "2026-08-10T08:00",
        expiryDate: "2026-08-18T18:00",
        status: "Active",
        body: "Water supply will be temporarily shut off for pipe upgrades."
    },
    {
        id: 2,
        title: "General Council Meeting Schedule",
        category: "General",
        scope: "Global",
        publishDate: "2026-08-12T10:00",
        expiryDate: "2026-08-25T17:00",
        status: "Pending",
        body: "Annual general meeting open to all community members."
    },
    {
        id: 3,
        title: "Emergency Power Grid Repairs",
        category: "Electricity",
        scope: "Ward 1",
        publishDate: "2026-08-01T06:00",
        expiryDate: "2026-08-02T18:00",
        status: "Archived",
        body: "Substation repairs scheduled following severe weather."
    }
];

let selectedNoticeId = null;

// DOM Selectors
const tableBody = document.getElementById("noticesTableBody");
const inspectorContent = document.getElementById("inspectorContent");
const modal = document.getElementById("noticeModal");
const noticeForm = document.getElementById("noticeForm");

// Metric Counters
const countActive = document.getElementById("countActive");
const countPending = document.getElementById("countPending");
const countArchived = document.getElementById("countArchived");

// Initialize Application
document.addEventListener("DOMContentLoaded", () => {
    renderTable();
    updateMetrics();
    setupEventListeners();
});

// Render Main Notices Table
function renderTable() {
    const searchVal = document.getElementById("searchInput").value.toLowerCase();
    const catVal = document.getElementById("filterCategory").value;
    const statusVal = document.getElementById("filterStatus").value;

    const filtered = notices.filter(n => {
        const matchesSearch = n.title.toLowerCase().includes(searchVal);
        const matchesCat = catVal === "" || n.category === catVal;
        const matchesStatus = statusVal === "" || n.status === statusVal;
        return matchesSearch && matchesCat && matchesStatus;
    });

    tableBody.innerHTML = filtered.map(n => `
        <tr onclick="inspectNotice(${n.id})" style="cursor: pointer;">
            <td><strong>${n.title}</strong></td>
            <td>${n.category}</td>
            <td>${n.scope}</td>
            <td>${n.publishDate.replace("T", " ")}</td>
            <td>${n.expiryDate.replace("T", " ")}</td>
            <td><span class="badge badge-${n.status.toLowerCase()}">${n.status}</span></td>
            <td>
                <button class="btn btnSecondary" onclick="event.stopPropagation(); inspectNotice(${n.id})">
                    <i class="fa-solid fa-eye"></i>
                </button>
            </td>
        </tr>
    `).join("");
}

// UPDATE TOP METRIX
function updateMetrics() {
    countActive.textContent = notices.filter(n => n.status === "Active").length;
    countPending.textContent = notices.filter(n => n.status === "Pending").length;
    countArchived.textContent = notices.filter(n => n.status === "Archived").length;
}

// Inspect Selected Notice & Display Context Actions
function inspectNotice(id) {
    selectedNoticeId = id;
    const notice = notices.find(n => n.id === id);
    if (!notice) return;

    inspectorContent.classList.remove("inspectorEmpty");
    inspectorContent.innerHTML = `
        <div style="display:flex; flex-direction:column; gap:0.75rem; margin-top: 1rem;">
            <h4>${notice.title}</h4>
            <p><strong>Category:</strong> ${notice.category}</p>
            <p><strong>Target:</strong> ${notice.scope}</p>
            <p><strong>Published:</strong> ${notice.publishDate.replace("T", " ")}</p>
            <p><strong>Expires:</strong> ${notice.expiryDate.replace("T", " ")}</p>
            <p><strong>Body:</strong> ${notice.body}</p>
            <hr style="border-top:1px solid var(--border-color); margin: 0.5rem 0;">
            
            ${notice.status === 'Pending' ? `
                <button class="btn btnSuccess" onclick="approveNotice(${notice.id})">
                    <i class="fa-solid fa-check"></i> Approve & Publish
                </button>
            ` : ''}

            ${notice.status !== 'Archived' ? `
                <button class="btn btnDanger" onclick="softDeleteNotice(${notice.id})">
                    <i class="fa-solid fa-box-archive"></i> Delete
                </button>
            ` : `
                <button class="btn btnSecondary" onclick="restoreNotice(${notice.id})">
                    <i class="fa-solid fa-rotate-left"></i> Restore Notice
                </button>
            `}
        </div>
    `;
}

// SOFT DELETE
function softDeleteNotice(id) {
    const notice = notices.find(n => n.id === id);
    if (notice) {
        notice.status = "Archived";
        refreshUI();
        inspectNotice(id);
    }
}

// RESTORE DELETED CONTENT
function restoreNotice(id) {
    const notice = notices.find(n => n.id === id);
    if (notice) {
        notice.status = "Active";
        refreshUI();
        inspectNotice(id);
    }
}

// APPROVE PENDING NOTICES
function approveNotice(id) {
    const notice = notices.find(n => n.id === id);
    if (notice) {
        notice.status = "Active";
        refreshUI();
        inspectNotice(id);
    }
}

// REFRESHER HELPER
function refreshUI() {
    renderTable();
    updateMetrics();
}

// SETUP THE EVENTS HANDLER
function setupEventListeners() {
    document.getElementById("searchInput").addEventListener("input", renderTable);
    document.getElementById("filterCategory").addEventListener("change", renderTable);
    document.getElementById("filterStatus").addEventListener("change", renderTable);

    // MODAL CONTROL
    const openModalBtn = document.getElementById("btnOpenModal");
    const closeModalBtn = document.getElementById("btnCloseModal");
    const cancelModalBtn = document.getElementById("btnCancelModal");

    openModalBtn.onclick = () => modal.style.display = "flex";
    closeModalBtn.onclick = () => modal.style.display = "none";
    cancelModalBtn.onclick = () => modal.style.display = "none";

    // FORM SUBMISSION
    noticeForm.addEventListener("submit", (e) => {
        e.preventDefault();
        
        const newNotice = {
            id: notices.length + 1,
            title: document.getElementById("notice-title").value,
            category: document.getElementById("notice-category").value,
            scope: document.getElementById("notice-scope").value,
            publishDate: document.getElementById("notice-publish").value,
            expiryDate: document.getElementById("notice-expiry").value,
            status: "Pending", // Default lifecycle state requiring admin approval
            body: document.getElementById("notice-body").value
        };

        notices.push(newNotice);
        noticeForm.reset();
        modal.style.display = "none";
        refreshUI();
    });
}