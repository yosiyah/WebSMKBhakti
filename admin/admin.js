// Admin Panel JavaScript
let currentUser = null;

// Initialize admin panel
document.addEventListener("DOMContentLoaded", function () {
  // Check authentication
  checkAuth();

  // Initialize navigation
  initNavigation();

  // Load dashboard data
  loadDashboard();

  // Initialize forms
  initForms();
});

// Authentication check
async function checkAuth() {
  try {
    // For now, simulate authentication - in real implementation, check session/token
    const response = await fetch("../api/auth.php?action=check");
    const result = await response.json();

    if (result.authenticated) {
      currentUser = result.user;
      document.getElementById("user-info").textContent =
        currentUser.nama_lengkap || currentUser.username;
    } else {
      // Redirect to login if not authenticated
      window.location.href = "../login.html";
    }
  } catch (error) {
    console.error("Auth check failed:", error);
    // For development, assume authenticated
    currentUser = { username: "admin", nama_lengkap: "Administrator" };
    document.getElementById("user-info").textContent = currentUser.nama_lengkap;
  }
}

// Navigation handling
function initNavigation() {
  const navLinks = document.querySelectorAll(".nav-link");
  const sections = document.querySelectorAll(".admin-section");

  navLinks.forEach((link) => {
    link.addEventListener("click", function (e) {
      e.preventDefault();

      // Remove active class from all links and sections
      navLinks.forEach((l) => l.classList.remove("active"));
      sections.forEach((s) => s.classList.remove("active"));

      // Add active class to clicked link and corresponding section
      this.classList.add("active");
      const sectionId = this.getAttribute("data-section");
      document.getElementById(sectionId + "-section").classList.add("active");

      // Load section data
      loadSectionData(sectionId);
    });
  });
}

// Load section specific data
function loadSectionData(section) {
  switch (section) {
    case "dashboard":
      loadDashboard();
      break;
    case "kegiatan":
      loadKegiatan();
      break;
    case "galeri":
      loadGaleri();
      break;
  }
}

// Dashboard functions
async function loadDashboard() {
  try {
    // Load stats
    const [kegiatanRes, galeriRes] = await Promise.all([
      fetch("../api/kegiatan.php"),
      fetch("../api/galeri.php"),
    ]);

    const kegiatan = await kegiatanRes.json();
    const galeri = await galeriRes.json();

    // Update stats
    document.getElementById("total-kegiatan").textContent = kegiatan.length;
    document.getElementById("total-galeri").textContent = galeri.length;

    const publishedKegiatan = kegiatan.filter(
      (k) => k.status === "published"
    ).length;
    const draftKegiatan = kegiatan.filter((k) => k.status === "draft").length;

    document.getElementById("published-kegiatan").textContent =
      publishedKegiatan;
    document.getElementById("draft-kegiatan").textContent = draftKegiatan;

    // Load recent activity
    loadRecentActivity();
  } catch (error) {
    console.error("Error loading dashboard:", error);
  }
}

async function loadRecentActivity() {
  try {
    const [kegiatanRes, galeriRes] = await Promise.all([
      fetch("../api/kegiatan.php?limit=5"),
      fetch("../api/galeri.php?limit=5"),
    ]);

    const kegiatan = await kegiatanRes.json();
    const galeri = await galeriRes.json();

    let activityHtml = "";

    // Add recent kegiatan
    kegiatan.slice(0, 3).forEach((item) => {
      activityHtml += `
                <div class="activity-item">
                    <span class="activity-type">Kegiatan</span>
                    <span class="activity-title">${item.judul}</span>
                    <span class="activity-date">${
                      item.created_at
                        ? new Date(item.created_at).toLocaleDateString("id-ID")
                        : "N/A"
                    }</span>
                </div>
            `;
    });

    // Add recent galeri
    galeri.slice(0, 2).forEach((item) => {
      activityHtml += `
                <div class="activity-item">
                    <span class="activity-type">Galeri</span>
                    <span class="activity-title">${item.judul || "Foto"}</span>
                    <span class="activity-date">${
                      item.created_at
                        ? new Date(item.created_at).toLocaleDateString("id-ID")
                        : "N/A"
                    }</span>
                </div>
            `;
    });

    document.getElementById("recent-activity-list").innerHTML =
      activityHtml || "<p>Tidak ada aktivitas terbaru</p>";
  } catch (error) {
    console.error("Error loading recent activity:", error);
    document.getElementById("recent-activity-list").innerHTML =
      "<p>Error memuat aktivitas</p>";
  }
}

// Kegiatan management
async function loadKegiatan() {
  try {
    const response = await fetch("../api/kegiatan.php");
    const kegiatan = await response.json();

    const kegiatanList = document.getElementById("kegiatan-list");
    kegiatanList.innerHTML = "";

    if (kegiatan.length === 0) {
      kegiatanList.innerHTML =
        '<div class="empty-state"><p>Tidak ada kegiatan</p></div>';
      return;
    }

    kegiatan.forEach((item) => {
      const itemDiv = document.createElement("div");
      itemDiv.className = "content-item";
      itemDiv.innerHTML = `
                <div class="item-info">
                    <h4>${item.judul}</h4>
                    <p>${item.excerpt || item.isi.substring(0, 100) + "..."}</p>
                    <small>Status: ${item.status} | Tanggal: ${
        item.tanggal_formatted
      }</small>
                </div>
                <div class="item-actions">
                    <button class="btn-edit" onclick="editKegiatan(${
                      item.id
                    })">Edit</button>
                    <button class="btn-delete" onclick="deleteKegiatan(${
                      item.id
                    })">Hapus</button>
                </div>
            `;
      kegiatanList.appendChild(itemDiv);
    });
  } catch (error) {
    console.error("Error loading kegiatan:", error);
    document.getElementById("kegiatan-list").innerHTML =
      "<p>Error memuat kegiatan</p>";
  }
}

// Galeri management
async function loadGaleri() {
  try {
    const response = await fetch("../api/galeri.php");
    const galeri = await response.json();

    const galeriList = document.getElementById("galeri-list");
    galeriList.innerHTML = "";

    if (galeri.length === 0) {
      galeriList.innerHTML =
        '<div class="empty-state"><p>Tidak ada foto galeri</p></div>';
      return;
    }

    galeri.forEach((item) => {
      const itemDiv = document.createElement("div");
      itemDiv.className = "content-item";
      itemDiv.innerHTML = `
                <div class="item-info">
                    <img src="../${item.gambar}" alt="${
        item.judul || "Foto"
      }" style="width: 100px; height: 100px; object-fit: cover; border-radius: 4px;">
                    <div>
                        <h4>${item.judul || "Tanpa Judul"}</h4>
                        <p>${item.deskripsi || "Tanpa deskripsi"}</p>
                        <small>Kategori: ${item.kategori}</small>
                    </div>
                </div>
                <div class="item-actions">
                    <button class="btn-edit" onclick="editGaleri(${
                      item.id
                    })">Edit</button>
                    <button class="btn-delete" onclick="deleteGaleri(${
                      item.id
                    })">Hapus</button>
                </div>
            `;
      galeriList.appendChild(itemDiv);
    });
  } catch (error) {
    console.error("Error loading galeri:", error);
    document.getElementById("galeri-list").innerHTML =
      "<p>Error memuat galeri</p>";
  }
}

// Form handling
function initForms() {
  // Kegiatan form
  const kegiatanForm = document.getElementById("kegiatan-form");
  if (kegiatanForm) {
    kegiatanForm.addEventListener("submit", handleKegiatanSubmit);
  }

  // Galeri form
  const galeriForm = document.getElementById("galeri-form");
  if (galeriForm) {
    galeriForm.addEventListener("submit", handleGaleriSubmit);
  }
}

// Kegiatan CRUD operations
async function handleKegiatanSubmit(e) {
  e.preventDefault();

  const formData = {
    judul: document.getElementById("kegiatan-judul").value,
    isi: document.getElementById("kegiatan-isi").value,
    tanggal: document.getElementById("kegiatan-tanggal").value,
    kategori: document.getElementById("kegiatan-kategori").value,
    gambar: document.getElementById("kegiatan-gambar").value,
    status: document.getElementById("kegiatan-status").value,
  };

  try {
    const method =
      document.getElementById("kegiatan-form").dataset.mode === "edit"
        ? "PUT"
        : "POST";
    const url =
      method === "PUT"
        ? `../api/kegiatan.php?id=${
            document.getElementById("kegiatan-form").dataset.id
          }`
        : "../api/kegiatan.php";

    const response = await fetch(url, {
      method: method,
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(formData),
    });

    const result = await response.json();

    if (result.success) {
      alert("Kegiatan berhasil disimpan!");
      closeModal();
      loadKegiatan();
      loadDashboard();
    } else {
      alert("Error: " + result.error);
    }
  } catch (error) {
    console.error("Error saving kegiatan:", error);
    alert("Error menyimpan kegiatan");
  }
}

// Galeri CRUD operations
async function handleGaleriSubmit(e) {
  e.preventDefault();

  const formData = {
    judul: document.getElementById("galeri-judul").value,
    deskripsi: document.getElementById("galeri-deskripsi").value,
    tanggal: document.getElementById("galeri-tanggal").value,
    kategori: document.getElementById("galeri-kategori").value,
    gambar: document.getElementById("galeri-gambar").value,
  };

  try {
    const method =
      document.getElementById("galeri-form").dataset.mode === "edit"
        ? "PUT"
        : "POST";
    const url =
      method === "PUT"
        ? `../api/galeri.php?id=${
            document.getElementById("galeri-form").dataset.id
          }`
        : "../api/galeri.php";

    const response = await fetch(url, {
      method: method,
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(formData),
    });

    const result = await response.json();

    if (result.success) {
      alert("Foto galeri berhasil disimpan!");
      closeModal();
      loadGaleri();
      loadDashboard();
    } else {
      alert("Error: " + result.error);
    }
  } catch (error) {
    console.error("Error saving galeri:", error);
    alert("Error menyimpan foto galeri");
  }
}

// Edit functions
async function editKegiatan(id) {
  try {
    const response = await fetch(`../api/kegiatan.php?id=${id}`);
    const kegiatan = await response.json();

    // Populate form
    document.getElementById("kegiatan-judul").value = kegiatan.judul;
    document.getElementById("kegiatan-isi").value = kegiatan.isi;
    document.getElementById("kegiatan-tanggal").value = kegiatan.tanggal;
    document.getElementById("kegiatan-kategori").value = kegiatan.kategori;
    document.getElementById("kegiatan-gambar").value = kegiatan.gambar || "";
    document.getElementById("kegiatan-status").value = kegiatan.status;

    // Set form mode
    document.getElementById("kegiatan-form").dataset.mode = "edit";
    document.getElementById("kegiatan-form").dataset.id = id;
    document.getElementById("kegiatan-modal-title").textContent =
      "Edit Kegiatan";

    // Show modal
    document.getElementById("kegiatan-modal").style.display = "flex";
  } catch (error) {
    console.error("Error loading kegiatan for edit:", error);
    alert("Error memuat data kegiatan");
  }
}

async function editGaleri(id) {
  try {
    const response = await fetch(`../api/galeri.php?id=${id}`);
    const item = await response.json();

    // Populate form
    document.getElementById("galeri-judul").value = item.judul || "";
    document.getElementById("galeri-deskripsi").value = item.deskripsi || "";
    document.getElementById("galeri-tanggal").value = item.tanggal || "";
    document.getElementById("galeri-kategori").value = item.kategori;
    document.getElementById("galeri-gambar").value = item.gambar;

    // Set form mode
    document.getElementById("galeri-form").dataset.mode = "edit";
    document.getElementById("galeri-form").dataset.id = id;
    document.getElementById("galeri-modal-title").textContent = "Edit Foto";

    // Show modal
    document.getElementById("galeri-modal").style.display = "flex";
  } catch (error) {
    console.error("Error loading galeri for edit:", error);
    alert("Error memuat data galeri");
  }
}

// Delete functions
async function deleteKegiatan(id) {
  if (!confirm("Apakah Anda yakin ingin menghapus kegiatan ini?")) {
    return;
  }

  try {
    const response = await fetch(`../api/kegiatan.php?id=${id}`, {
      method: "DELETE",
    });

    const result = await response.json();

    if (result.success) {
      alert("Kegiatan berhasil dihapus!");
      loadKegiatan();
      loadDashboard();
    } else {
      alert("Error: " + result.error);
    }
  } catch (error) {
    console.error("Error deleting kegiatan:", error);
    alert("Error menghapus kegiatan");
  }
}

async function deleteGaleri(id) {
  if (!confirm("Apakah Anda yakin ingin menghapus foto ini?")) {
    return;
  }

  try {
    const response = await fetch(`../api/galeri.php?id=${id}`, {
      method: "DELETE",
    });

    const result = await response.json();

    if (result.success) {
      alert("Foto berhasil dihapus!");
      loadGaleri();
      loadDashboard();
    } else {
      alert("Error: " + result.error);
    }
  } catch (error) {
    console.error("Error deleting galeri:", error);
    alert("Error menghapus foto");
  }
}

// Modal functions
function closeModal() {
  document.getElementById("kegiatan-modal").style.display = "none";
  document.getElementById("galeri-modal").style.display = "none";

  // Reset forms
  document.getElementById("kegiatan-form").reset();
  document.getElementById("galeri-form").reset();
  document.getElementById("kegiatan-form").dataset.mode = "";
  document.getElementById("galeri-form").dataset.mode = "";
  document.getElementById("kegiatan-modal-title").textContent =
    "Tambah Kegiatan";
  document.getElementById("galeri-modal-title").textContent = "Tambah Foto";
}

// Logout function
document.getElementById("logout-btn").addEventListener("click", function () {
  if (confirm("Apakah Anda yakin ingin logout?")) {
    // Clear session/token
    window.location.href = "../login.html";
  }
});

// Add event listeners for modal close buttons
document.addEventListener("DOMContentLoaded", function () {
  // Modal close buttons
  document.querySelectorAll(".modal-close").forEach((btn) => {
    btn.addEventListener("click", closeModal);
  });

  // Modal background click to close
  document.querySelectorAll(".modal").forEach((modal) => {
    modal.addEventListener("click", function (e) {
      if (e.target === modal) {
        closeModal();
      }
    });
  });

  // Add buttons
  document
    .getElementById("add-kegiatan-btn")
    .addEventListener("click", function () {
      document.getElementById("kegiatan-modal").style.display = "flex";
    });

  document
    .getElementById("add-galeri-btn")
    .addEventListener("click", function () {
      document.getElementById("galeri-modal").style.display = "flex";
    });
});
