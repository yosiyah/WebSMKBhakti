// Tab functionality for classes page
function initTabs() {
  const tabBtns = document.querySelectorAll(".tab-btn");
  const gradeContents = document.querySelectorAll(".grade-content");

  tabBtns.forEach((btn) => {
    btn.addEventListener("click", () => {
      // Remove active class from all buttons
      tabBtns.forEach((b) => b.classList.remove("active"));
      // Add active class to clicked button
      btn.classList.add("active");

      // Hide all grade contents
      gradeContents.forEach((content) => (content.style.display = "none"));

      // Show selected grade content
      const gradeId = btn.getAttribute("data-grade");
      const selectedContent = document.getElementById(`grade-${gradeId}`);
      if (selectedContent) {
        selectedContent.style.display = "block";
      }
    });
  });
}

// News filter functionality
function initNewsFilters() {
  const filterBtns = document.querySelectorAll(".filter-btn");

  filterBtns.forEach((btn) => {
    btn.addEventListener("click", () => {
      // Remove active class from all buttons
      filterBtns.forEach((b) => b.classList.remove("active"));
      // Add active class to clicked button
      btn.classList.add("active");

      const category = btn.getAttribute("data-category");
      // In a real implementation, this would filter the news items
      console.log("Filtering by category:", category);
    });
  });
}

// Form validation
function initFormValidation() {
  const forms = document.querySelectorAll("form");

  forms.forEach((form) => {
    form.addEventListener("submit", (e) => {
      e.preventDefault();

      // Basic validation
      const requiredFields = form.querySelectorAll("[required]");
      let isValid = true;

      requiredFields.forEach((field) => {
        if (!field.value.trim()) {
          isValid = false;
          field.style.borderColor = "red";
        } else {
          field.style.borderColor = "#ccc";
        }
      });

      if (isValid) {
        alert("Form submitted successfully!");
        form.reset();
      } else {
        alert("Please fill in all required fields.");
      }
    });
  });
}

// Calendar functionality
let currentDate = new Date();

function initCalendar() {
  const calendarGrid = document.querySelector(".calendar-grid");
  const currentMonthElement = document.getElementById("current-month");
  const prevButton = document.getElementById("prev-month");
  const nextButton = document.getElementById("next-month");

  if (!calendarGrid || !currentMonthElement) return;

  function renderCalendar() {
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();

    // Update month display
    const monthNames = [
      "Januari",
      "Februari",
      "Maret",
      "April",
      "Mei",
      "Juni",
      "Juli",
      "Agustus",
      "September",
      "Oktober",
      "November",
      "Desember",
    ];
    currentMonthElement.textContent = `${monthNames[month]} ${year}`;

    // Clear existing calendar days (keep the day headers)
    const existingDays = calendarGrid.querySelectorAll(".calendar-day");
    existingDays.forEach((day) => day.remove());

    // Get first day of month and last day of month
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const startDate = new Date(firstDay);
    startDate.setDate(startDate.getDate() - firstDay.getDay());

    // Create calendar days
    for (let i = 0; i < 42; i++) {
      const dayElement = document.createElement("div");
      dayElement.className = "calendar-day";

      const dayDate = new Date(startDate);
      dayDate.setDate(startDate.getDate() + i);

      const dayNumber = dayDate.getDate();

      // Check if day is in current month
      if (dayDate.getMonth() === month) {
        dayElement.textContent = dayNumber;

        // Check if it's today
        const today = new Date();
        if (dayDate.toDateString() === today.toDateString()) {
          dayElement.classList.add("today");
        }

        // Check if day has events
        if (hasEvent(dayDate)) {
          dayElement.classList.add("has-event");
        }

        // Add click event
        dayElement.addEventListener("click", () => showEventDetails(dayDate));
      } else {
        dayElement.textContent = dayNumber;
        dayElement.classList.add(
          dayDate.getMonth() < month ? "prev-month" : "next-month"
        );
      }

      calendarGrid.appendChild(dayElement);
    }
  }

  // Event navigation
  prevButton.addEventListener("click", () => {
    currentDate.setMonth(currentDate.getMonth() - 1);
    renderCalendar();
  });

  nextButton.addEventListener("click", () => {
    currentDate.setMonth(currentDate.getMonth() + 1);
    renderCalendar();
  });

  // Sample events data
  function hasEvent(date) {
    const events = [
      { date: "2024-09-15", title: "Hari Kemerdekaan RI" },
      { date: "2024-09-20", title: "Pembagian Rapor Semester 1" },
      { date: "2024-09-25", title: "Lomba Keterampilan Siswa" },
      { date: "2024-10-01", title: "Hari Olahraga Siswa" },
      { date: "2024-10-15", title: "Ujian Tengah Semester" },
    ];

    const dateString = date.toISOString().split("T")[0];
    return events.some((event) => event.date === dateString);
  }

  function showEventDetails(date) {
    const eventDetails = document.getElementById("event-details");
    const eventTitle = document.getElementById("event-title");
    const eventDescription = document.getElementById("event-description");
    const eventDate = document.getElementById("event-date");
    const eventTime = document.getElementById("event-time");
    const eventLocation = document.getElementById("event-location");

    const events = {
      "2024-09-15": {
        title: "Hari Kemerdekaan RI",
        description:
          "Libur nasional - Hari Kemerdekaan Republik Indonesia ke-79. Semua kegiatan sekolah dihentikan.",
        time: "Seharian",
        location: "Libur Nasional",
      },
      "2024-09-20": {
        title: "Pembagian Rapor Semester 1",
        description:
          "Pembagian rapor untuk siswa kelas 10 dan 11. Orang tua diminta hadir tepat waktu.",
        time: "08:00 - 12:00",
        location: "Aula Sekolah",
      },
      "2024-09-25": {
        title: "Lomba Keterampilan Siswa",
        description:
          "Kompetisi antar kelas dalam berbagai bidang keterampilan vokasi.",
        time: "09:00 - 15:00",
        location: "Workshop dan Lab Sekolah",
      },
      "2024-10-01": {
        title: "Hari Olahraga Siswa",
        description:
          "Kegiatan olahraga antar kelas dengan berbagai cabang olahraga.",
        time: "07:00 - 14:00",
        location: "Lapangan Olahraga Sekolah",
      },
      "2024-10-15": {
        title: "Ujian Tengah Semester",
        description: "Pelaksanaan Ujian Tengah Semester untuk semua kelas.",
        time: "08:00 - 12:00",
        location: "Ruang Kelas Masing-masing",
      },
    };

    const dateString = date.toISOString().split("T")[0];
    const event = events[dateString];

    if (event) {
      eventTitle.textContent = event.title;
      eventDescription.textContent = event.description;
      eventDate.textContent = date.toLocaleDateString("id-ID", {
        weekday: "long",
        year: "numeric",
        month: "long",
        day: "numeric",
      });
      eventTime.textContent = event.time;
      eventLocation.textContent = event.location;

      // Show event details
      eventDetails.style.display = "block";
      eventDetails.scrollIntoView({ behavior: "smooth" });
    } else {
      eventDetails.style.display = "none";
    }
  }

  // Initial render
  renderCalendar();
}

// Login functionality
function initLoginSystem() {
  const loginForm = document.getElementById("loginForm");
  const loginBtn = document.querySelector(".login-btn");

  if (loginForm) {
    loginForm.addEventListener("submit", handleLogin);
  }

  // Check if user is already logged in
  checkLoginStatus();

  // Update login button based on login status
  updateLoginButton();
}

function handleLogin(e) {
  e.preventDefault();

  const username = document.getElementById("username").value;
  const password = document.getElementById("password").value;
  const remember = document.getElementById("remember").checked;

  // Simple authentication (demo purposes)
  const user = authenticateUser(username, password);

  if (user) {
    // Store user session
    const sessionData = {
      username: user.username,
      role: user.role,
      name: user.name,
      loginTime: new Date().toISOString(),
      remember: remember,
    };

    if (remember) {
      localStorage.setItem("userSession", JSON.stringify(sessionData));
    } else {
      sessionStorage.setItem("userSession", JSON.stringify(sessionData));
    }

    // Show success message
    showLoginSuccess(user.name);

    // Redirect after a short delay
    setTimeout(() => {
      window.location.href = "index.html";
    }, 2000);
  } else {
    showLoginError();
  }
}

function authenticateUser(username, password) {
  // Demo user database
  const users = {
    admin: {
      username: "admin",
      password: "admin123",
      role: "admin",
      name: "Administrator",
    },
    guru: { username: "guru", password: "guru123", role: "guru", name: "Guru" },
    siswa: {
      username: "siswa",
      password: "siswa123",
      role: "siswa",
      name: "Siswa",
    },
    ortu: {
      username: "ortu",
      password: "ortu123",
      role: "orangtua",
      name: "Orang Tua",
    },
  };

  const user = users[username];
  if (user && user.password === password) {
    return user;
  }

  return null;
}

function checkLoginStatus() {
  const sessionData =
    localStorage.getItem("userSession") ||
    sessionStorage.getItem("userSession");

  if (sessionData) {
    const user = JSON.parse(sessionData);
    showWelcomeMessage(user.name, user.role);
  }
}

function showWelcomeMessage(name, role) {
  const mainContent = document.querySelector("main .container");
  if (!mainContent) return;

  const welcomeDiv = document.createElement("div");
  welcomeDiv.className = "welcome-message";
  welcomeDiv.innerHTML = `
    <strong>Selamat datang, ${name}!</strong>
    <p>Anda login sebagai ${role}. Nikmati fitur tambahan yang tersedia.</p>
  `;

  mainContent.insertBefore(welcomeDiv, mainContent.firstChild);
}

function showLoginSuccess(name) {
  const form = document.getElementById("loginForm");
  form.innerHTML = `
    <div style="text-align: center; color: #28a745;">
      <h3>✅ Login Berhasil!</h3>
      <p>Selamat datang, ${name}!</p>
      <p>Anda akan diarahkan ke halaman utama...</p>
    </div>
  `;
}

function showLoginError() {
  const existingError = document.querySelector(".login-error");
  if (existingError) {
    existingError.remove();
  }

  const errorDiv = document.createElement("div");
  errorDiv.className = "login-error";
  errorDiv.style.cssText = `
    background-color: #f8d7da;
    color: #721c24;
    padding: 1rem;
    border-radius: 4px;
    margin-bottom: 1rem;
    border: 1px solid #f5c6cb;
  `;
  errorDiv.textContent = "Username atau password salah. Silakan coba lagi.";

  const form = document.getElementById("loginForm");
  form.insertBefore(errorDiv, form.firstChild);

  // Clear form
  document.getElementById("username").value = "";
  document.getElementById("password").value = "";
  document.getElementById("username").focus();
}

function updateLoginButton() {
  const loginBtn = document.querySelector(".login-btn");
  if (!loginBtn) return;

  const sessionData =
    localStorage.getItem("userSession") ||
    sessionStorage.getItem("userSession");

  if (sessionData) {
    const user = JSON.parse(sessionData);
    loginBtn.textContent = `Logout (${user.name})`;
    loginBtn.href = "#";
    loginBtn.addEventListener("click", handleLogout);
  } else {
    loginBtn.textContent = "Login/Daftar";
    loginBtn.href = "login.html";
  }
}

function handleLogout(e) {
  e.preventDefault();

  if (confirm("Apakah Anda yakin ingin logout?")) {
    localStorage.removeItem("userSession");
    sessionStorage.removeItem("userSession");
    window.location.href = "index.html";
  }
}

// Gallery functionality
function initGallery() {
  const filterBtns = document.querySelectorAll(".gallery-filters .filter-btn");
  const galleryItems = document.querySelectorAll(".gallery-item");
  const lightbox = document.getElementById("lightbox");
  const lightboxImage = document.getElementById("lightbox-image");
  const lightboxTitle = document.getElementById("lightbox-title");
  const lightboxDescription = document.getElementById("lightbox-description");
  const lightboxDate = document.getElementById("lightbox-date");
  const lightboxClose = document.querySelector(".lightbox-close");

  if (!filterBtns.length || !galleryItems.length) return;

  // Filter functionality
  filterBtns.forEach((btn) => {
    btn.addEventListener("click", () => {
      // Remove active class from all buttons
      filterBtns.forEach((b) => b.classList.remove("active"));
      // Add active class to clicked button
      btn.classList.add("active");

      const category = btn.getAttribute("data-category");

      // Filter gallery items
      galleryItems.forEach((item) => {
        if (
          category === "all" ||
          item.getAttribute("data-category") === category
        ) {
          item.style.display = "block";
          item.style.animation = "fadeIn 0.5s ease";
        } else {
          item.style.display = "none";
        }
      });
    });
  });

  // Lightbox functionality
  galleryItems.forEach((item) => {
    item.addEventListener("click", () => {
      const img = item.querySelector("img");
      const overlay = item.querySelector(".gallery-overlay");
      const title = overlay.querySelector("h3");
      const description = overlay.querySelector("p");
      const date = overlay.querySelector(".gallery-date");

      lightboxImage.src = img.src;
      lightboxImage.alt = img.alt;
      lightboxTitle.textContent = title.textContent;
      lightboxDescription.textContent = description.textContent;
      lightboxDate.textContent = date.textContent;

      lightbox.style.display = "flex";
      document.body.style.overflow = "hidden";
    });
  });

  // Close lightbox
  if (lightboxClose) {
    lightboxClose.addEventListener("click", closeLightbox);
  }

  if (lightbox) {
    lightbox.addEventListener("click", (e) => {
      if (e.target === lightbox) {
        closeLightbox();
      }
    });
  }

  // Close lightbox with ESC key
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && lightbox && lightbox.style.display === "flex") {
      closeLightbox();
    }
  });

  function closeLightbox() {
    if (lightbox) {
      lightbox.style.display = "none";
      document.body.style.overflow = "auto";
    }
  }
}

// Registration form functionality
function initRegistrationForm() {
  const steps = document.querySelectorAll(".step");
  const formSteps = document.querySelectorAll(".form-step");
  const prevBtn = document.getElementById("prevBtn");
  const nextBtn = document.getElementById("nextBtn");
  const submitBtn = document.getElementById("submitBtn");
  const dataSummary = document.getElementById("data-summary");

  if (!steps.length || !formSteps.length) return;

  let currentStep = 1;
  const totalSteps = steps.length;

  // Navigation functions
  function showStep(stepNumber) {
    // Update step indicators
    steps.forEach((step, index) => {
      if (index + 1 === stepNumber) {
        step.classList.add("active");
      } else {
        step.classList.remove("active");
      }
    });

    // Show corresponding form step
    formSteps.forEach((formStep, index) => {
      if (index + 1 === stepNumber) {
        formStep.classList.add("active");
      } else {
        formStep.classList.remove("active");
      }
    });

    // Update navigation buttons
    prevBtn.style.display = stepNumber === 1 ? "none" : "inline-block";
    nextBtn.style.display = stepNumber === totalSteps ? "none" : "inline-block";
    submitBtn.style.display =
      stepNumber === totalSteps ? "inline-block" : "none";

    currentStep = stepNumber;
  }

  function validateCurrentStep() {
    const currentFormStep = document.getElementById(`step${currentStep}`);
    const requiredFields = currentFormStep.querySelectorAll("[required]");
    let isValid = true;

    requiredFields.forEach((field) => {
      if (!field.value.trim()) {
        isValid = false;
        field.style.borderColor = "#dc3545";
        field.style.boxShadow = "0 0 0 0.2rem rgba(220, 53, 69, 0.25)";
      } else {
        field.style.borderColor = "#28a745";
        field.style.boxShadow = "0 0 0 0.2rem rgba(40, 167, 69, 0.25)";
      }
    });

    return isValid;
  }

  function collectFormData() {
    const formData = {
      personal: {
        nama_lengkap: document.getElementById("nama_lengkap").value,
        nama_panggilan: document.getElementById("nama_panggilan").value,
        tempat_lahir: document.getElementById("tempat_lahir").value,
        tanggal_lahir: document.getElementById("tanggal_lahir").value,
        jenis_kelamin: document.getElementById("jenis_kelamin").value,
        agama: document.getElementById("agama").value,
        alamat: document.getElementById("alamat").value,
        no_telepon: document.getElementById("no_telepon").value,
        email: document.getElementById("email").value,
      },
      parents: {
        nama_ayah: document.getElementById("nama_ayah").value,
        nama_ibu: document.getElementById("nama_ibu").value,
        pekerjaan_ayah: document.getElementById("pekerjaan_ayah").value,
        pekerjaan_ibu: document.getElementById("pekerjaan_ibu").value,
        alamat_orangtua: document.getElementById("alamat_orangtua").value,
        no_telepon_ortu: document.getElementById("no_telepon_ortu").value,
        penghasilan: document.getElementById("penghasilan").value,
      },
      academic: {
        sekolah_asal: document.getElementById("sekolah_asal").value,
        alamat_sekolah: document.getElementById("alamat_sekolah").value,
        nisn: document.getElementById("nisn").value,
        tahun_lulus: document.getElementById("tahun_lulus").value,
        nilai_rata_rata: document.getElementById("nilai_rata_rata").value,
        jurusan_pilihan: document.getElementById("jurusan_pilihan").value,
        prestasi: document.getElementById("prestasi").value,
      },
    };

    return formData;
  }

  function displayDataSummary() {
    const formData = collectFormData();

    let summary = `📋 DATA PENDAFTARAN SISWA BARU
═══════════════════════════════════════════════

👤 DATA PRIBADI:
• Nama Lengkap: ${formData.personal.nama_lengkap}
• Nama Panggilan: ${formData.personal.nama_panggilan || "-"}
• Tempat/Tanggal Lahir: ${formData.personal.tempat_lahir}, ${
      formData.personal.tanggal_lahir
    }
• Jenis Kelamin: ${
      formData.personal.jenis_kelamin === "L" ? "Laki-laki" : "Perempuan"
    }
• Agama: ${formData.personal.agama}
• Alamat: ${formData.personal.alamat}
• No. Telepon: ${formData.personal.no_telepon || "-"}
• Email: ${formData.personal.email || "-"}

👨‍👩‍👧‍👦 DATA ORANG TUA:
• Nama Ayah: ${formData.parents.nama_ayah}
• Nama Ibu: ${formData.parents.nama_ibu}
• Pekerjaan Ayah: ${formData.parents.pekerjaan_ayah || "-"}
• Pekerjaan Ibu: ${formData.parents.pekerjaan_ibu || "-"}
• Alamat: ${formData.parents.alamat_orangtua}
• No. Telepon: ${formData.parents.no_telepon_ortu}
• Penghasilan: ${formData.parents.penghasilan || "-"}

🎓 DATA AKADEMIK:
• Sekolah Asal: ${formData.academic.sekolah_asal}
• Alamat Sekolah: ${formData.academic.alamat_sekolah || "-"}
• NISN: ${formData.academic.nisn || "-"}
• Tahun Lulus: ${formData.academic.tahun_lulus}
• Nilai Rata-rata: ${formData.academic.nilai_rata_rata || "-"}
• Jurusan Pilihan: ${formData.academic.jurusan_pilihan}
• Prestasi: ${formData.academic.prestasi || "-"}

═══════════════════════════════════════════════
Tanggal Pendaftaran: ${new Date().toLocaleDateString("id-ID")}
`;

    dataSummary.textContent = summary;
  }

  // Event listeners
  nextBtn.addEventListener("click", () => {
    if (validateCurrentStep()) {
      if (currentStep < totalSteps) {
        showStep(currentStep + 1);
        if (currentStep === totalSteps) {
          displayDataSummary();
        }
      }
    } else {
      alert("Mohon lengkapi semua field yang wajib diisi.");
    }
  });

  prevBtn.addEventListener("click", () => {
    if (currentStep > 1) {
      showStep(currentStep - 1);
    }
  });

  submitBtn.addEventListener("click", (e) => {
    e.preventDefault();

    const agreeTerms = document.getElementById("agree_terms").checked;

    if (!agreeTerms) {
      alert(
        "Anda harus menyetujui syarat dan ketentuan untuk melanjutkan pendaftaran."
      );
      return;
    }

    if (validateCurrentStep()) {
      // Simulate form submission
      alert(
        "✅ Pendaftaran berhasil dikirim!\n\nNomor pendaftaran Anda: PP-" +
          Date.now() +
          "\n\nSilakan simpan nomor ini dan bawa berkas persyaratan saat verifikasi.\n\nInformasi lebih lanjut akan dikirim ke email yang terdaftar."
      );

      // Reset form
      document.querySelectorAll("input, textarea, select").forEach((field) => {
        field.value = "";
        field.style.borderColor = "#ccc";
        field.style.boxShadow = "none";
      });

      document.getElementById("agree_terms").checked = false;

      // Reset to first step
      showStep(1);
    }
  });

  // Real-time validation
  document
    .querySelectorAll("input[required], textarea[required], select[required]")
    .forEach((field) => {
      field.addEventListener("blur", () => {
        if (field.value.trim()) {
          field.style.borderColor = "#28a745";
          field.style.boxShadow = "0 0 0 0.2rem rgba(40, 167, 69, 0.25)";
        } else {
          field.style.borderColor = "#dc3545";
          field.style.boxShadow = "0 0 0 0.2rem rgba(220, 53, 69, 0.25)";
        }
      });
    });

  // Initialize first step
  showStep(1);
}

// Dropdown menu functionality
function initDropdownMenus() {
  const dropdownToggles = document.querySelectorAll(".dropdown-toggle");

  dropdownToggles.forEach((toggle) => {
    toggle.addEventListener("click", (e) => {
      e.preventDefault();

      // Close all other dropdowns
      document.querySelectorAll(".dropdown-menu").forEach((menu) => {
        if (menu !== toggle.nextElementSibling) {
          menu.classList.remove("show");
        }
      });

      // Toggle current dropdown
      const dropdownMenu = toggle.nextElementSibling;
      dropdownMenu.classList.toggle("show");
    });
  });

  // Close dropdown when clicking outside
  document.addEventListener("click", (e) => {
    if (!e.target.closest(".dropdown")) {
      document.querySelectorAll(".dropdown-menu").forEach((menu) => {
        menu.classList.remove("show");
      });
    }
  });

  // Close dropdown on escape key
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      document.querySelectorAll(".dropdown-menu").forEach((menu) => {
        menu.classList.remove("show");
      });
    }
  });
}

// Header scroll effect
function initHeaderScroll() {
  const header = document.querySelector("header");
  let lastScrollTop = 0;

  window.addEventListener("scroll", () => {
    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;

    if (scrollTop > lastScrollTop && scrollTop > 100) {
      // Scrolling down - make header more compact
      header.style.padding = "0.5rem 0";
      header.style.boxShadow = "0 2px 20px rgba(0, 0, 0, 0.15)";
    } else {
      // Scrolling up or at top - restore original padding
      header.style.padding = "1rem 0";
      header.style.boxShadow = "0 2px 10px rgba(0, 0, 0, 0.1)";
    }

    lastScrollTop = scrollTop;
  });
}

// Back to Top functionality
function initBackToTop() {
  const backToTopBtn = document.getElementById("backToTop");

  if (!backToTopBtn) return;

  // Show/hide button based on scroll position
  window.addEventListener("scroll", () => {
    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;

    if (scrollTop > 300) {
      backToTopBtn.classList.add("show");
    } else {
      backToTopBtn.classList.remove("show");
    }
  });

  // Scroll to top when clicked
  backToTopBtn.addEventListener("click", () => {
    window.scrollTo({
      top: 0,
      behavior: "smooth",
    });
  });
}

// ==========================================
// 3D EFFECTS & INTERACTIVE ANIMATIONS
// ==========================================

// 3D Mouse Tracking Effect
function init3DMouseTracking() {
  const cards = document.querySelectorAll(
    ".card, .faculty-card, .subject-card, .category-card, .news-item, .gallery-item"
  );
  const hero = document.querySelector(".hero");
  const buttons = document.querySelectorAll(
    ".btn-primary, .btn-secondary, .submit-btn, .filter-btn, .tab-btn"
  );

  // Mouse tracking for cards
  cards.forEach((card) => {
    card.addEventListener("mousemove", (e) => {
      const rect = card.getBoundingClientRect();
      const x = e.clientX - rect.left;
      const y = e.clientY - rect.top;

      const centerX = rect.width / 2;
      const centerY = rect.height / 2;

      const rotateX = (y - centerY) / 10;
      const rotateY = (centerX - x) / 10;

      card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateZ(30px) scale(1.02)`;
    });

    card.addEventListener("mouseleave", () => {
      card.style.transform = "";
    });
  });

  // Mouse tracking for hero section
  if (hero) {
    hero.addEventListener("mousemove", (e) => {
      const rect = hero.getBoundingClientRect();
      const x = e.clientX - rect.left;
      const y = e.clientY - rect.top;

      const centerX = rect.width / 2;
      const centerY = rect.height / 2;

      const moveX = (x - centerX) / 50;
      const moveY = (y - centerY) / 50;

      hero.style.transform = `translate(${moveX}px, ${moveY}px)`;
    });

    hero.addEventListener("mouseleave", () => {
      hero.style.transform = "";
    });
  }

  // Enhanced button interactions
  buttons.forEach((button) => {
    button.addEventListener("mousedown", () => {
      button.style.transform = "translateZ(15px) rotateX(-5deg) scale(0.98)";
    });

    button.addEventListener("mouseup", () => {
      button.style.transform = "";
    });

    button.addEventListener("mouseleave", () => {
      button.style.transform = "";
    });
  });
}

// 3D Scroll Effects
function init3DScrollEffects() {
  const scrollElements = document.querySelectorAll(
    ".card, .faculty-card, .subject-card, .category-card, .news-item, .gallery-item, .floating-element"
  );

  function checkScroll() {
    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
    const windowHeight = window.innerHeight;

    scrollElements.forEach((element, index) => {
      const elementTop = element.offsetTop;
      const elementHeight = element.offsetHeight;

      if (
        scrollTop + windowHeight > elementTop &&
        scrollTop < elementTop + elementHeight
      ) {
        const progress =
          (scrollTop + windowHeight - elementTop) /
          (windowHeight + elementHeight);
        const translateZ = progress * 50 - 25;
        const rotateX = progress * 10 - 5;

        element.style.transform = `translateZ(${translateZ}px) rotateX(${rotateX}deg)`;
        element.classList.add("scroll-reveal");
      }
    });
  }

  window.addEventListener("scroll", checkScroll);
  checkScroll(); // Initial check
}

// 3D Parallax Background
function init3DParallax() {
  const parallaxElements = document.querySelectorAll(
    ".hero, .card, .gallery-item"
  );

  window.addEventListener("scroll", () => {
    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;

    parallaxElements.forEach((element, index) => {
      const speed = (index + 1) * 0.5;
      const yPos = -(scrollTop * speed);
      const rotateX = scrollTop * 0.01;

      element.style.transform = `translate3d(0, ${yPos}px, 0) rotateX(${rotateX}deg)`;
    });
  });
}

// 3D Floating Elements Animation
function init3DFloatingElements() {
  const floatingElements = document.querySelectorAll(".floating-element");

  floatingElements.forEach((element, index) => {
    const delay = index * 0.5;
    element.style.animationDelay = `${delay}s`;
    element.classList.add("floating-element");
  });
}

// 3D Interactive Gallery
function init3DInteractiveGallery() {
  const galleryItems = document.querySelectorAll(".gallery-item");

  galleryItems.forEach((item) => {
    item.addEventListener("mouseenter", () => {
      // Add 3D depth to sibling elements
      const siblings = Array.from(galleryItems).filter((el) => el !== item);
      siblings.forEach((sibling) => {
        sibling.style.transform = "translateZ(-20px) scale(0.95)";
        sibling.style.opacity = "0.7";
      });
    });

    item.addEventListener("mouseleave", () => {
      // Reset sibling elements
      const siblings = Array.from(galleryItems).filter((el) => el !== item);
      siblings.forEach((sibling) => {
        sibling.style.transform = "";
        sibling.style.opacity = "";
      });
    });
  });
}

// 3D Modal Enhancements
function init3DModalEnhancements() {
  const modals = document.querySelectorAll(".modal");

  modals.forEach((modal) => {
    modal.addEventListener("show", () => {
      modal.style.animation =
        "modal-entrance-3d 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94)";
    });
  });
}

// 3D Form Interactions
function init3DFormInteractions() {
  const formElements = document.querySelectorAll("input, textarea, select");

  formElements.forEach((element) => {
    element.addEventListener("focus", () => {
      element.style.transform = "translateZ(10px) rotateX(-2deg) scale(1.01)";
      element.style.transition =
        "all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94)";
    });

    element.addEventListener("blur", () => {
      element.style.transform = "";
    });
  });
}

// 3D Navigation Effects
function init3DNavigationEffects() {
  const navLinks = document.querySelectorAll(".nav-menu li a");

  navLinks.forEach((link) => {
    link.addEventListener("mouseenter", () => {
      // Add 3D glow effect
      link.style.textShadow =
        "0 0 20px rgba(255, 215, 0, 0.8), 0 0 40px rgba(255, 215, 0, 0.6)";
      link.style.transform = "translateZ(15px) rotateX(-5deg)";
    });

    link.addEventListener("mouseleave", () => {
      link.style.textShadow = "";
      link.style.transform = "";
    });
  });
}

// 3D Loading Animations
function init3DLoadingAnimations() {
  const loadingElements = document.querySelectorAll(".loading-state");

  loadingElements.forEach((element) => {
    element.innerHTML = `
      <div style="display: flex; justify-content: center; align-items: center; gap: 10px;">
        <div class="loading-cube" style="width: 20px; height: 20px; background: linear-gradient(135deg, #003366, #ffd700); animation: loading-cube-3d 1.5s ease-in-out infinite;"></div>
        <div class="loading-cube" style="width: 20px; height: 20px; background: linear-gradient(135deg, #003366, #ffd700); animation: loading-cube-3d 1.5s ease-in-out infinite; animation-delay: 0.2s;"></div>
        <div class="loading-cube" style="width: 20px; height: 20px; background: linear-gradient(135deg, #003366, #ffd700); animation: loading-cube-3d 1.5s ease-in-out infinite; animation-delay: 0.4s;"></div>
      </div>
      <p style="text-align: center; margin-top: 10px;">Memuat konten 3D...</p>
    `;
  });
}

// 3D Performance Monitoring
function init3DPerformanceMonitoring() {
  let frameCount = 0;
  let lastTime = performance.now();

  function monitorPerformance() {
    frameCount++;
    const currentTime = performance.now();

    if (currentTime - lastTime >= 1000) {
      const fps = Math.round((frameCount * 1000) / (currentTime - lastTime));

      // Adjust 3D effects based on performance
      if (fps < 30) {
        document.body.classList.add("reduce-3d-effects");
      } else {
        document.body.classList.remove("reduce-3d-effects");
      }

      frameCount = 0;
      lastTime = currentTime;
    }

    requestAnimationFrame(monitorPerformance);
  }

  requestAnimationFrame(monitorPerformance);
}

// ==========================================
// MOBILE HAMBURGER MENU FUNCTIONALITY
// ==========================================

function initMobileMenu() {
  const hamburger = document.getElementById('hamburger-menu');
  const mobileMenuOverlay = document.getElementById('mobile-menu-overlay');
  const mobileMenu = document.getElementById('mobile-menu');
  const mobileKurikulumToggle = document.getElementById('mobile-kurikulum-toggle');
  const mobileKurikulumMenu = document.getElementById('mobile-kurikulum-menu');

  if (!hamburger || !mobileMenuOverlay || !mobileMenu) return;

  // Toggle mobile menu
  hamburger.addEventListener('click', () => {
    hamburger.classList.toggle('active');
    mobileMenuOverlay.classList.toggle('active');
    mobileMenu.classList.toggle('active');
    document.body.classList.toggle('mobile-menu-open');
  });

  // Close mobile menu when clicking overlay
  mobileMenuOverlay.addEventListener('click', (e) => {
    if (e.target === mobileMenuOverlay) {
      closeMobileMenu();
    }
  });

  // Mobile dropdown toggle
  if (mobileKurikulumToggle && mobileKurikulumMenu) {
    mobileKurikulumToggle.addEventListener('click', (e) => {
      e.preventDefault();
      mobileKurikulumMenu.classList.toggle('active');
    });
  }

  // Close mobile menu when clicking menu links
  const mobileMenuLinks = mobileMenu.querySelectorAll('a:not(.dropdown-toggle)');
  mobileMenuLinks.forEach(link => {
    link.addEventListener('click', () => {
      closeMobileMenu();
    });
  });

  // Close mobile menu on escape key
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && mobileMenuOverlay.classList.contains('active')) {
      closeMobileMenu();
    }
  });

  function closeMobileMenu() {
    hamburger.classList.remove('active');
    mobileMenuOverlay.classList.remove('active');
    mobileMenu.classList.remove('active');
    document.body.classList.remove('mobile-menu-open');

    // Close mobile dropdown if open
    if (mobileKurikulumMenu) {
      mobileKurikulumMenu.classList.remove('active');
    }
  }
}

// Initialize all 3D functionality when DOM is loaded
document.addEventListener("DOMContentLoaded", () => {
  initTabs();
  initNewsFilters();
  initFormValidation();
  initCalendar();
  initLoginSystem();
  initGallery();
  initRegistrationForm();
  initDropdownMenus();
  initHeaderScroll(); // Add header scroll effect
  initBackToTop(); // Add back to top functionality
  updateLoginButton(); // Update login button on all pages
  initMobileMenu(); // Add mobile hamburger menu

  // Initialize 3D Effects
  init3DMouseTracking();
  init3DScrollEffects();
  init3DParallax();
  init3DFloatingElements();
  init3DInteractiveGallery();
  init3DModalEnhancements();
  init3DFormInteractions();
  init3DNavigationEffects();
  init3DLoadingAnimations();
  init3DPerformanceMonitoring();
});
