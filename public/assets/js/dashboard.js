// Dashboard core functionality
document.addEventListener("DOMContentLoaded", function () {
  // Theme toggle functionality
  const themeToggle = document.getElementById("theme-toggle");
  if (themeToggle) {
    themeToggle.addEventListener("click", function () {
      const currentTheme = document.documentElement.getAttribute("data-theme");
      const newTheme = currentTheme === "dark" ? "light" : "dark";

      document.documentElement.setAttribute("data-theme", newTheme);
      localStorage.setItem("theme", newTheme);
    });
  }

  // User menu dropdown
  const userButton = document.querySelector(".user-button");
  const dropdownMenu = document.querySelector(".dropdown-menu");
  if (userButton && dropdownMenu) {
    userButton.addEventListener("click", function (e) {
      e.stopPropagation();
      dropdownMenu.classList.toggle("active");
    });

    // Close dropdown when clicking outside
    document.addEventListener("click", function () {
      dropdownMenu.classList.remove("active");
    });

    dropdownMenu.addEventListener("click", function (e) {
      e.stopPropagation();
    });
  }

  // Mobile sidebar toggle
  const sidebarToggle = document.getElementById("sidebar-toggle");
  const sidebar = document.querySelector(".sidebar");
  if (sidebarToggle && sidebar) {
    sidebarToggle.addEventListener("click", function () {
      sidebar.classList.toggle("active");
    });

    // Close sidebar when clicking outside on mobile
    document.addEventListener("click", function (e) {
      if (
        window.innerWidth <= 768 &&
        !sidebar.contains(e.target) &&
        !sidebarToggle.contains(e.target)
      ) {
        sidebar.classList.remove("active");
      }
    });
  }

  // Flash message auto-hide
  const flashMessage = document.querySelector(".alert");
  if (flashMessage) {
    setTimeout(function () {
      flashMessage.style.opacity = "0";
      setTimeout(function () {
        flashMessage.remove();
      }, 300);
    }, 5000);
  }
});

// Utility function to format numbers
function formatNumber(number, decimals = 0) {
  return new Intl.NumberFormat("en-US", {
    minimumFractionDigits: decimals,
    maximumFractionDigits: decimals,
  }).format(number);
}

// Utility function to format currency
function formatCurrency(amount, currency = "USD") {
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: currency,
  }).format(amount);
}

// Utility function to format dates
function formatDate(date, options = {}) {
  if (typeof date === "string") {
    date = new Date(date);
  }
  return new Intl.DateTimeFormat("en-US", options).format(date);
}

// Utility function for API calls
async function apiCall(endpoint, options = {}) {
  try {
    const response = await fetch(endpoint, {
      ...options,
      headers: {
        "Content-Type": "application/json",
        ...options.headers,
      },
    });

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    return await response.json();
  } catch (error) {
    console.error("API call failed:", error);
    throw error;
  }
}

// Debounce utility for search inputs
function debounce(func, wait) {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
}
