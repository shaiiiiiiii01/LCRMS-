document.addEventListener("DOMContentLoaded", () => {
    const passwordToggle = document.querySelector("[data-admin-toggle-password]");
    const passwordInput = document.querySelector("#adminPassword");

    if (passwordToggle && passwordInput) {
        passwordToggle.addEventListener("click", () => {
            const isPassword = passwordInput.type === "password";
            passwordInput.type = isPassword ? "text" : "password";
            passwordToggle.setAttribute("aria-label", isPassword ? "Hide password" : "Show password");
        });
    }

    document.querySelectorAll("[data-password-visibility-toggle]").forEach((toggle) => {
        toggle.addEventListener("click", () => {
            const targetId = toggle.dataset.passwordTarget || "";
            const target = document.getElementById(targetId);

            if (!target) {
                return;
            }

            if (target.dataset.passwordUnchanged === "true" && target.value === "••••••••") {
                const toast = document.querySelector("[data-user-toast]");

                if (toast) {
                    toast.textContent = "Saved passwords are encrypted. Type a new password to reveal it here.";
                    toast.hidden = false;
                    window.setTimeout(() => {
                        toast.hidden = true;
                    }, 3200);
                }

                target.focus();
                target.select();
                return;
            }

            const isPassword = target.type === "password";
            target.type = isPassword ? "text" : "password";
            toggle.setAttribute("aria-label", isPassword ? "Hide password" : "Show password");
            target.focus();
        });
    });

    document.querySelectorAll("[data-admin-sidebar-toggle]").forEach((button) => {
        button.addEventListener("click", () => {
            document.body.classList.toggle("admin-sidebar-open");
        });
    });

    document.querySelectorAll("[data-admin-sidebar-close]").forEach((backdrop) => {
        backdrop.addEventListener("click", () => {
            document.body.classList.remove("admin-sidebar-open");
        });
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
            document.body.classList.remove("admin-sidebar-open");
        }
    });

    document.querySelectorAll(".settings-form").forEach((form) => {
        form.addEventListener("submit", (event) => event.preventDefault());
    });

    const escapeAdminHtml = (value) => String(value ?? "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");

    const formatAdminDate = (value, withTime = false) => {
        if (!value) {
            return "";
        }

        const date = new Date(String(value).replace(" ", "T"));

        if (Number.isNaN(date.getTime())) {
            return String(value);
        }

        const options = {
            year: "numeric",
            month: "short",
            day: "2-digit",
        };

        if (withTime) {
            options.hour = "numeric";
            options.minute = "2-digit";
        }

        return date.toLocaleString(undefined, options);
    };

    const createAdminCaseDetailField = (labelText, value, options = {}) => {
        const group = document.createElement("div");
        const label = document.createElement("label");
        const fieldValue = value || "";
        let field;

        group.className = options.wide ? "form-group wide" : "form-group";
        label.textContent = labelText;

        if (options.type === "textarea") {
            field = document.createElement("textarea");
            field.rows = 5;
            field.value = fieldValue;
            field.readOnly = !options.editable;
        } else if (options.type === "select") {
            field = document.createElement("select");
            field.disabled = !options.editable;

            const choices = options.choices || [{ value: fieldValue, label: fieldValue || "Not set" }];

            choices.forEach((choice) => {
                const option = document.createElement("option");
                option.value = choice.value;
                option.textContent = choice.label;
                option.selected = choice.value === fieldValue;
                field.appendChild(option);
            });
        } else {
            field = document.createElement("input");
            field.type = options.type === "date" ? "date" : "text";
            field.value = fieldValue;
            field.readOnly = !options.editable;
        }

        if (options.name) {
            field.name = options.name;
        }

        group.append(label, field);

        return group;
    };

    const createAdminCaseDetailSection = (title, copy, fields, gridClass = "section-grid") => {
        const section = document.createElement("section");
        const heading = document.createElement("div");
        const headingTitle = document.createElement("h3");
        const headingCopy = document.createElement("p");
        const grid = document.createElement("div");

        section.className = "form-section";
        heading.className = "form-section-title";
        headingTitle.textContent = title;
        headingCopy.textContent = copy;
        grid.className = gridClass;

        fields.forEach((field) => grid.appendChild(field));
        heading.append(headingTitle, headingCopy);
        section.append(heading, grid);

        return section;
    };

    const renderAdminCaseDetails = (body, caseData, apiUrl = "cases_api.php") => {
        const form = document.createElement("form");
        const grid = document.createElement("div");
        const feedback = document.createElement("p");
        const actions = document.createElement("div");
        const saveButton = document.createElement("button");

        const statusChoices = [
            { value: "CFA (Call for Action)", label: "CFA" },
            { value: "Mediation", label: "M" },
            { value: "Conciliation", label: "C" },
            { value: "For Conciliation Stage", label: "For Conciliation Stage" },
            { value: "Settled", label: "Settled" },
            { value: "Endorsed", label: "Endorsed" },
            { value: "Dismissed", label: "Dismissed" },
        ];

        if (caseData.case_status && !statusChoices.some((choice) => choice.value === caseData.case_status)) {
            statusChoices.unshift({ value: caseData.case_status, label: caseData.case_status });
        }

        form.className = "case-form case-details-form admin-edit-case-form";
        form.setAttribute("aria-label", "Editable case details");
        grid.className = "case-form-grid";
        feedback.className = "admin-case-save-message";
        feedback.hidden = true;
        actions.className = "admin-case-modal-actions";
        saveButton.className = "admin-primary-button compact";
        saveButton.type = "submit";
        saveButton.textContent = "Save Changes";

        grid.append(
            createAdminCaseDetailSection(
                "Case Identification",
                "Basic filing details used to classify and locate the case record.",
                [
                    createAdminCaseDetailField("Case Number", caseData.case_number),
                    createAdminCaseDetailField("Case Title", caseData.case_title, { name: "case_title", editable: true, wide: true }),
                    createAdminCaseDetailField("Complainant Title", caseData.complainant_title, { name: "complainant_title", editable: true }),
                    createAdminCaseDetailField("Nature of Case", caseData.nature_of_case, { name: "nature_of_case", editable: true }),
                ]
            ),
            createAdminCaseDetailSection(
                "Schedule and Status",
                "Filing dates, case movement, and current case status.",
                [
                    createAdminCaseDetailField("Date Filed", caseData.date_filed, { type: "date", name: "date_filed", editable: true }),
                    createAdminCaseDetailField("Date of Initial Confrontation", caseData.date_initial_confrontation, { type: "date", name: "date_initial_confrontation", editable: true }),
                    createAdminCaseDetailField("Case Status", caseData.case_status, { type: "select", name: "case_status", editable: true, choices: statusChoices }),
                    createAdminCaseDetailField("Date of Settlement / Award", caseData.date_settlement_award, { type: "date", name: "date_settlement_award", editable: true }),
                    createAdminCaseDetailField("Date of Execution", caseData.date_execution, { type: "date", name: "date_execution", editable: true }),
                ],
                "date-status-grid"
            ),
            createAdminCaseDetailSection(
                "Case Narrative",
                "Documented incident details and agreement reached during proceedings.",
                [
                    createAdminCaseDetailField("Detailed Case Description", caseData.detailed_case_description, { type: "textarea", name: "detailed_case_description", editable: true, wide: true }),
                    createAdminCaseDetailField("Main Point of Agreement", caseData.main_point_of_agreement, { type: "textarea", name: "main_point_of_agreement", editable: true, wide: true }),
                ],
                "section-grid narrative-grid"
            )
        );

        actions.appendChild(saveButton);
        form.append(grid, feedback, actions);

        form.addEventListener("submit", async (event) => {
            event.preventDefault();
            saveButton.disabled = true;
            saveButton.textContent = "Saving...";
            feedback.hidden = true;
            feedback.classList.remove("is-error", "is-success");

            try {
                const separator = apiUrl.includes("?") ? "&" : "?";
                const formData = new FormData(form);
                formData.append("id", caseData.id);

                const response = await fetch(`${apiUrl}${separator}action=update`, {
                    method: "POST",
                    body: formData,
                    headers: { Accept: "application/json" },
                });
                const data = await response.json();

                if (!response.ok || !data.success) {
                    throw new Error(data.message || "Unable to update case record.");
                }

                feedback.textContent = data.message || "Case record updated successfully.";
                feedback.classList.add("is-success");
                feedback.hidden = false;
                window.setTimeout(() => window.location.reload(), 700);
            } catch (error) {
                feedback.textContent = error.message || "Unable to update case record.";
                feedback.classList.add("is-error");
                feedback.hidden = false;
            } finally {
                saveButton.disabled = false;
                saveButton.textContent = "Save Changes";
            }
        });

        body.replaceChildren(form);
    };

    const openAdminCaseDetailsModal = async (caseId, apiUrl = "cases_api.php") => {
        const existingModal = document.querySelector("[data-case-details-modal]");
        const overlay = document.createElement("div");
        const dialog = document.createElement("div");
        const header = document.createElement("div");
        const titleWrap = document.createElement("div");
        const title = document.createElement("h2");
        const subtitle = document.createElement("p");
        const closeButton = document.createElement("button");
        const body = document.createElement("div");
        const loading = document.createElement("p");

        if (existingModal) {
            existingModal.remove();
        }

        overlay.className = "case-details-modal-overlay";
        overlay.dataset.caseDetailsModal = "";
        dialog.className = "case-details-modal";
        dialog.setAttribute("role", "dialog");
        dialog.setAttribute("aria-modal", "true");
        dialog.setAttribute("aria-labelledby", "caseDetailsModalTitle");
        header.className = "case-details-modal-header";
        titleWrap.className = "case-details-modal-title";
        title.id = "caseDetailsModalTitle";
        title.textContent = "Case Details";
        subtitle.textContent = "Read-only completed case record from the database.";
        closeButton.className = "case-details-modal-close";
        closeButton.type = "button";
        closeButton.setAttribute("aria-label", "Close case details");
        closeButton.textContent = "X";
        body.className = "case-details-modal-body";
        loading.className = "case-details-loading";
        loading.textContent = "Loading case details...";

        titleWrap.append(title, subtitle);
        header.append(titleWrap, closeButton);
        body.appendChild(loading);
        dialog.append(header, body);
        overlay.appendChild(dialog);
        document.body.appendChild(overlay);
        document.body.classList.add("admin-modal-open");

        const closeModal = () => {
            overlay.remove();
            document.body.classList.remove("admin-modal-open");
            document.removeEventListener("keydown", handleModalKeydown);
        };

        const handleModalKeydown = (event) => {
            if (event.key === "Escape") {
                event.preventDefault();
                closeModal();
            }
        };

        closeButton.addEventListener("click", closeModal);
        overlay.addEventListener("click", (event) => {
            if (event.target === overlay) {
                closeModal();
            }
        });
        document.addEventListener("keydown", handleModalKeydown);
        closeButton.focus();

        try {
            const separator = apiUrl.includes("?") ? "&" : "?";
            const response = await fetch(`${apiUrl}${separator}action=detail&id=${encodeURIComponent(caseId)}`, {
                headers: { Accept: "application/json" },
            });
            const data = await response.json();

            if (!response.ok || !data.success || !data.case) {
                body.innerHTML = `<p class="case-details-loading">${escapeAdminHtml(data.message || "Unable to load case details.")}</p>`;
                return;
            }

            renderAdminCaseDetails(body, data.case, apiUrl);
        } catch (error) {
            body.innerHTML = `<p class="case-details-loading">${escapeAdminHtml(error.message || "Unable to load case details.")}</p>`;
        }
    };

    const initializeAdminCaseFilters = (root = document) => {
        root.querySelectorAll("[data-admin-case-filters]").forEach((form) => {
            if (form.dataset.caseFiltersReady === "true") {
                return;
            }

            form.dataset.caseFiltersReady = "true";
            const searchInput = form.querySelector("[data-admin-case-search]");
            let searchTimer = null;

            if (searchInput) {
                searchInput.addEventListener("input", () => {
                    window.clearTimeout(searchTimer);
                    searchTimer = window.setTimeout(() => form.submit(), 300);
                });
            }

            form.querySelectorAll("[data-admin-case-filter]").forEach((filter) => {
                filter.addEventListener("change", () => form.submit());
            });
        });
    };

    const initializeAdminCaseRows = (root = document) => {
        root.querySelectorAll("[data-admin-cases]").forEach((caseArea) => {
            if (caseArea.dataset.adminCasesReady === "true") {
                return;
            }

            caseArea.dataset.adminCasesReady = "true";
            const apiUrl = caseArea.dataset.caseApi || "cases_api.php";

            caseArea.addEventListener("click", (event) => {
                const caseRow = event.target.closest("[data-admin-case-row]");

                if (!caseRow) {
                    return;
                }

                event.preventDefault();
                openAdminCaseDetailsModal(caseRow.dataset.caseId, apiUrl);
            });

            caseArea.querySelectorAll("[data-admin-case-row]").forEach((row) => {
                row.addEventListener("keydown", (event) => {
                    if (event.key !== "Enter" && event.key !== " ") {
                        return;
                    }

                    event.preventDefault();
                    openAdminCaseDetailsModal(row.dataset.caseId, apiUrl);
                });
            });
        });
    };

    const updateCaseFilterCards = (status) => {
        document.querySelectorAll("[data-case-filter-card]").forEach((card) => {
            card.classList.toggle("is-active", (card.dataset.caseFilterStatus || "") === status);
        });
    };

    const scrollToCaseSearch = () => {
        document.querySelector("#caseSearch")?.scrollIntoView({
            behavior: "smooth",
            block: "start",
        });
    };

    document.querySelectorAll("[data-case-filter-card]").forEach((card) => {
        card.addEventListener("click", async (event) => {
            const casesPanel = document.querySelector(".cases-panel");

            if (!casesPanel) {
                return;
            }

            event.preventDefault();
            casesPanel.classList.add("is-loading");

            try {
                const response = await fetch(card.href, {
                    headers: { Accept: "text/html" },
                });

                if (!response.ok) {
                    throw new Error("Unable to load filtered cases.");
                }

                const html = await response.text();
                const nextDocument = new DOMParser().parseFromString(html, "text/html");
                const nextCasesPanel = nextDocument.querySelector(".cases-panel");

                if (!nextCasesPanel) {
                    throw new Error("Filtered cases content was not found.");
                }

                casesPanel.innerHTML = nextCasesPanel.innerHTML;
                const nextUrl = new URL(card.href);
                const nextStatus = nextUrl.searchParams.get("status") || "";

                updateCaseFilterCards(nextStatus);
                initializeAdminCaseFilters(casesPanel);
                initializeAdminCaseRows(casesPanel);
                window.history.pushState({}, "", `${nextUrl.pathname}${nextUrl.search}#caseSearch`);
                scrollToCaseSearch();
            } catch (error) {
                window.location.href = card.href;
            } finally {
                casesPanel.classList.remove("is-loading");
            }
        });
    });

    initializeAdminCaseFilters();

    const dashboardCaseRows = document.querySelectorAll("[data-dashboard-case-row]");
    const dashboardViewButton = document.querySelector("[data-dashboard-view-selected]");
    let selectedDashboardCaseId = "";

    const selectDashboardCaseRow = (row) => {
        selectedDashboardCaseId = row.dataset.caseId || "";

        dashboardCaseRows.forEach((item) => {
            const isSelected = item === row;
            item.classList.toggle("is-selected", isSelected);
            item.setAttribute("aria-selected", isSelected ? "true" : "false");
        });

        if (dashboardViewButton) {
            dashboardViewButton.disabled = selectedDashboardCaseId === "";
        }
    };

    dashboardCaseRows.forEach((row) => {
        row.addEventListener("click", () => selectDashboardCaseRow(row));
        row.addEventListener("keydown", (event) => {
            if (event.key === "Enter" || event.key === " ") {
                event.preventDefault();
                selectDashboardCaseRow(row);
            }
        });
    });

    if (dashboardViewButton) {
        dashboardViewButton.addEventListener("click", () => {
            if (!selectedDashboardCaseId) {
                return;
            }

            window.location.href = `cases.php?case_id=${encodeURIComponent(selectedDashboardCaseId)}`;
        });
    }

    initializeAdminCaseRows();

    const autoOpenCaseId = new URLSearchParams(window.location.search).get("case_id");
    const autoOpenCaseArea = document.querySelector("[data-admin-cases]");

    if (autoOpenCaseId && autoOpenCaseArea) {
        openAdminCaseDetailsModal(autoOpenCaseId, autoOpenCaseArea.dataset.caseApi || "cases_api.php");
    }

    const userManagement = document.querySelector("[data-user-management]");

    if (!userManagement) {
        return;
    }

    const apiUrl = userManagement.dataset.userApi || "users_api.php";
    const tableBody = userManagement.querySelector("[data-user-table-body]");
    const tableFrame = userManagement.querySelector("[data-user-table-frame]");
    const emptyState = userManagement.querySelector("[data-user-empty-state]");
    const searchInput = userManagement.querySelector("[data-user-search]");
    const toast = userManagement.querySelector("[data-user-toast]");

    const userModal = userManagement.querySelector("[data-user-modal]");
    const userForm = userManagement.querySelector("[data-user-form]");
    const userIdInput = userManagement.querySelector("[data-user-id]");
    const nameInput = userManagement.querySelector("[data-user-name]");
    const usernameInput = userManagement.querySelector("[data-user-username]");
    const passwordField = userManagement.querySelector("[data-user-password]");
    const passwordFieldToggle = userManagement.querySelector("[data-password-target='managedPassword']");
    const roleInput = userManagement.querySelector("[data-user-role]");
    const userFormError = userManagement.querySelector("[data-user-form-error]");
    const userModalTitle = userManagement.querySelector("[data-user-modal-title]");
    const userModalSubtitle = userManagement.querySelector("[data-user-modal-subtitle]");
    const userSubmit = userManagement.querySelector("[data-user-submit]");
    const adminProfileForm = userManagement.querySelector("[data-admin-profile-form]");
    const adminProfileFullname = userManagement.querySelector("[data-admin-profile-fullname]");
    const adminProfileUsername = userManagement.querySelector("[data-admin-profile-username]");
    const adminProfilePassword = userManagement.querySelector("[data-admin-profile-password]");
    const adminProfileError = userManagement.querySelector("[data-admin-profile-error]");
    const adminProfileSubmit = userManagement.querySelector("[data-admin-profile-submit]");

    const deleteModal = userManagement.querySelector("[data-delete-modal]");
    const deleteUserName = userManagement.querySelector("[data-delete-user-name]");
    const confirmDeleteButton = userManagement.querySelector("[data-confirm-delete]");

    let users = [];
    let editingUserId = null;
    let deletingUserId = null;
    let toastTimer = null;
    let searchTimer = null;
    let adminProfileEditing = false;
    const unchangedPasswordMask = "••••••••";

    const escapeHtml = (value) => String(value ?? "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");

    const formatDate = (value) => {
        if (!value) {
            return "Not recorded";
        }

        const date = new Date(String(value).replace(" ", "T"));

        if (Number.isNaN(date.getTime())) {
            return String(value);
        }

        return date.toLocaleDateString(undefined, {
            year: "numeric",
            month: "short",
            day: "2-digit",
        });
    };

    const request = async (action, options = {}) => {
        const separator = apiUrl.includes("?") ? "&" : "?";
        const response = await fetch(`${apiUrl}${separator}action=${encodeURIComponent(action)}`, {
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
            },
            ...options,
        });

        const payload = await response.json().catch(() => ({
            success: false,
            message: "The server returned an invalid response.",
        }));

        if (!response.ok || !payload.success) {
            throw new Error(payload.message || "Request failed.");
        }

        return payload;
    };

    const showToast = (message) => {
        window.clearTimeout(toastTimer);
        toast.textContent = message;
        toast.hidden = false;
        toastTimer = window.setTimeout(() => {
            toast.hidden = true;
        }, 2800);
    };

    const setError = (message) => {
        userFormError.textContent = message;
        userFormError.hidden = false;
    };

    const clearError = () => {
        userFormError.textContent = "";
        userFormError.hidden = true;
    };

    const setAdminProfileError = (message) => {
        adminProfileError.textContent = message;
        adminProfileError.hidden = false;
    };

    const clearAdminProfileError = () => {
        adminProfileError.textContent = "";
        adminProfileError.hidden = true;
    };

    const updateVisibleAdminName = (admin) => {
        document.querySelectorAll(".admin-profile strong, .admin-sidebar-account strong").forEach((element) => {
            element.textContent = admin.fullname || admin.username || "Admin";
        });
    };

    const setAdminProfileEditing = (isEditing) => {
        adminProfileEditing = isEditing;
        [adminProfileFullname, adminProfileUsername, adminProfilePassword].forEach((field) => {
            if (field) {
                field.readOnly = !isEditing;
                field.tabIndex = isEditing ? 0 : -1;
            }
        });

        if (adminProfileSubmit) {
            adminProfileSubmit.textContent = isEditing ? "Save" : "Edit";
        }
    };

    const openModal = (modal) => {
        modal.hidden = false;
        document.body.classList.add("admin-modal-open");
    };

    const closeModal = (modal) => {
        modal.hidden = true;

        if (userModal.hidden && deleteModal.hidden) {
            document.body.classList.remove("admin-modal-open");
        }
    };

    const renderUsers = () => {
        if (!users.length) {
            tableBody.innerHTML = "";
            tableFrame.hidden = true;
            emptyState.hidden = false;
            return;
        }

        tableFrame.hidden = false;
        emptyState.hidden = true;
        tableBody.innerHTML = users.map((user) => `
            <tr class="user-management-row" data-user-row data-user-id="${escapeHtml(user.id)}" tabindex="0" role="button" aria-label="Edit ${escapeHtml(user.fullname)}">
                <td>${escapeHtml(user.fullname)}</td>
                <td>${escapeHtml(user.username)}</td>
                <td>
                    <div class="user-password-cell">
                        <span class="password-mask" aria-label="Password hidden">${escapeHtml(unchangedPasswordMask)}</span>
                        <button class="user-action-button edit" type="button" data-user-action="edit" data-user-id="${escapeHtml(user.id)}" aria-label="Edit ${escapeHtml(user.fullname)}">
                            <i class="fa-regular fa-pen-to-square" aria-hidden="true"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join("");
    };

    const loadUsers = async () => {
        const separator = apiUrl.includes("?") ? "&" : "?";
        const search = encodeURIComponent(searchInput.value.trim());
        const response = await fetch(`${apiUrl}${separator}action=list&search=${search}`, {
            headers: { Accept: "application/json" },
        });
        const payload = await response.json().catch(() => ({
            success: false,
            message: "The server returned an invalid response.",
        }));

        if (!response.ok || !payload.success) {
            throw new Error(payload.message || "Unable to load users.");
        }

        users = payload.users || [];
        renderUsers();
    };

    const loadAdminProfile = async () => {
        if (!adminProfileForm) {
            return;
        }

        const separator = apiUrl.includes("?") ? "&" : "?";
        const response = await fetch(`${apiUrl}${separator}action=admin_profile`, {
            headers: { Accept: "application/json" },
        });
        const payload = await response.json().catch(() => ({
            success: false,
            message: "The server returned an invalid response.",
        }));

        if (!response.ok || !payload.success || !payload.admin) {
            throw new Error(payload.message || "Unable to load admin profile.");
        }

        adminProfileFullname.value = payload.admin.fullname || "";
        adminProfileUsername.value = payload.admin.username || "";
        adminProfilePassword.value = unchangedPasswordMask;
        adminProfilePassword.dataset.passwordUnchanged = "true";
        setAdminProfileEditing(false);
        updateVisibleAdminName(payload.admin);
    };

    const openUserModal = (user = null) => {
        editingUserId = user ? Number(user.id) : null;
        userForm.reset();
        clearError();

        userIdInput.value = user ? user.id : "";
        nameInput.value = user ? user.fullname : "";
        usernameInput.value = user ? user.username : "";
        roleInput.value = "USER";
        passwordField.required = !user;
        passwordField.type = "password";
        passwordField.placeholder = "";
        passwordField.value = user ? unchangedPasswordMask : "";
        passwordField.dataset.passwordUnchanged = user ? "true" : "false";
        passwordFieldToggle?.setAttribute("aria-label", "Show password");
        userModalTitle.textContent = user ? "Edit User" : "Add User";
        userModalSubtitle.textContent = user ? "Update this user's access details." : "Create a system account for LCRMS access.";
        userSubmit.textContent = user ? "Save Changes" : "Add User";

        openModal(userModal);
        nameInput.focus();
    };

    const openDeleteModal = (user) => {
        deletingUserId = Number(user.id);
        deleteUserName.textContent = user.fullname;
        openModal(deleteModal);
        confirmDeleteButton.focus();
    };

    userManagement.querySelector("[data-open-user-modal]").addEventListener("click", () => {
        openUserModal();
    });

    userManagement.querySelectorAll("[data-close-user-modal]").forEach((button) => {
        button.addEventListener("click", () => closeModal(userModal));
    });

    userManagement.querySelectorAll("[data-close-delete-modal]").forEach((button) => {
        button.addEventListener("click", () => closeModal(deleteModal));
    });

    passwordField.addEventListener("input", () => {
        if (passwordField.dataset.passwordUnchanged === "true" && passwordField.value !== unchangedPasswordMask) {
            passwordField.dataset.passwordUnchanged = "false";
        }
    });

    userForm.addEventListener("submit", async (event) => {
        event.preventDefault();
        const passwordValue = passwordField.value.trim();
        const keepExistingPassword = Boolean(editingUserId)
            && (
                passwordField.dataset.passwordUnchanged === "true"
                || passwordValue === ""
                || passwordValue === unchangedPasswordMask
            );

        const payload = {
            id: userIdInput.value,
            fullname: nameInput.value.trim(),
            username: usernameInput.value.trim(),
            password: keepExistingPassword ? "" : passwordValue,
            role: "USER",
        };

        if (!payload.fullname || !payload.username) {
            setError("Full name and username are required.");
            return;
        }

        if (!editingUserId && !payload.password) {
            setError("Password is required for new users.");
            return;
        }

        if (payload.role !== "USER") {
            setError("Select a valid role.");
            return;
        }

        clearError();
        userSubmit.disabled = true;

        try {
            const result = await request(editingUserId ? "update" : "create", {
                method: "POST",
                body: JSON.stringify(payload),
            });

            closeModal(userModal);
            await loadUsers();
            showToast(result.message);
        } catch (error) {
            setError(error.message);
        } finally {
            userSubmit.disabled = false;
        }
    });

    if (adminProfileForm) {
        adminProfilePassword.addEventListener("input", () => {
            if (adminProfilePassword.dataset.passwordUnchanged === "true" && adminProfilePassword.value !== unchangedPasswordMask) {
                adminProfilePassword.dataset.passwordUnchanged = "false";
            }
        });

        adminProfileForm.addEventListener("submit", async (event) => {
            event.preventDefault();

            if (!adminProfileEditing) {
                setAdminProfileEditing(true);
                adminProfileFullname.focus();
                return;
            }

            const passwordValue = adminProfilePassword.value.trim();
            const keepExistingPassword = adminProfilePassword.dataset.passwordUnchanged === "true"
                || passwordValue === ""
                || passwordValue === unchangedPasswordMask;

            const payload = {
                fullname: adminProfileFullname.value.trim(),
                username: adminProfileUsername.value.trim(),
                password: keepExistingPassword ? "" : passwordValue,
            };

            if (!payload.fullname || !payload.username) {
                setAdminProfileError("Full name and username are required.");
                return;
            }

            clearAdminProfileError();
            adminProfileSubmit.disabled = true;

            try {
                const result = await request("update_admin_profile", {
                    method: "POST",
                    body: JSON.stringify(payload),
                });

                if (result.admin) {
                    updateVisibleAdminName(result.admin);
                }

                adminProfilePassword.value = unchangedPasswordMask;
                adminProfilePassword.dataset.passwordUnchanged = "true";
                setAdminProfileEditing(false);

                showToast(result.message || "Admin profile updated successfully.");
            } catch (error) {
                setAdminProfileError(error.message);
            } finally {
                adminProfileSubmit.disabled = false;
            }
        });
    }

    confirmDeleteButton.addEventListener("click", async () => {
        if (!deletingUserId) {
            return;
        }

        confirmDeleteButton.disabled = true;

        try {
            const result = await request("delete", {
                method: "POST",
                body: JSON.stringify({ id: deletingUserId }),
            });

            closeModal(deleteModal);
            deletingUserId = null;
            await loadUsers();
            showToast(result.message);
        } catch (error) {
            showToast(error.message);
        } finally {
            confirmDeleteButton.disabled = false;
        }
    });

    tableBody.addEventListener("click", (event) => {
        const actionButton = event.target.closest("[data-user-action]");
        const userRow = event.target.closest("[data-user-row]");

        if (!actionButton && !userRow) {
            return;
        }

        const userId = Number((actionButton || userRow).dataset.userId);
        const user = users.find((item) => Number(item.id) === userId);

        if (!user) {
            return;
        }

        if (!actionButton || actionButton.dataset.userAction === "edit") {
            openUserModal(user);
            return;
        }

        openDeleteModal(user);
    });

    tableBody.addEventListener("keydown", (event) => {
        if (event.key !== "Enter" && event.key !== " ") {
            return;
        }

        const userRow = event.target.closest("[data-user-row]");

        if (!userRow) {
            return;
        }

        event.preventDefault();
        const user = users.find((item) => Number(item.id) === Number(userRow.dataset.userId));

        if (user) {
            openUserModal(user);
        }
    });

    searchInput.addEventListener("input", () => {
        window.clearTimeout(searchTimer);
        searchTimer = window.setTimeout(() => {
            loadUsers().catch((error) => showToast(error.message));
        }, 180);
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
            closeModal(userModal);
            closeModal(deleteModal);
        }
    });

    loadAdminProfile().catch((error) => showToast(error.message));
    loadUsers().catch((error) => showToast(error.message));
});
