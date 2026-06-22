document.addEventListener("DOMContentLoaded", () => {
    const passwordToggle = document.querySelector("[data-admin-toggle-password]");
    const passwordInput = document.querySelector("#adminPassword");

    if (passwordToggle && passwordInput) {
        const setPasswordIcon = (isHidden) => {
            passwordToggle.innerHTML = isHidden
                ? '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12z"></path><circle cx="12" cy="12" r="3"></circle><path d="M4 4l16 16"></path></svg>'
                : '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
        };

        setPasswordIcon(true);
        passwordToggle.addEventListener("click", () => {
            const isPassword = passwordInput.type === "password";
            passwordInput.type = isPassword ? "text" : "password";
            passwordToggle.setAttribute("aria-label", isPassword ? "Hide password" : "Show password");
            setPasswordIcon(!isPassword);
        });
    }

    const loginForm = document.querySelector("[data-login-form]");
    const loginLoading = document.querySelector("[data-login-loading]");
    const loginSubmit = document.querySelector("[data-login-submit]");

    if (loginForm && loginLoading) {
        loginForm.addEventListener("submit", (event) => {
            if (loginForm.dataset.loginDelayComplete === "true") {
                return;
            }

            event.preventDefault();
            loginLoading.setAttribute("aria-hidden", "false");
            document.body.classList.add("is-login-loading");

            if (loginSubmit) {
                loginSubmit.disabled = true;
                loginSubmit.querySelector("span").textContent = "Signing in...";
            }

            window.setTimeout(() => {
                loginForm.dataset.loginDelayComplete = "true";
                loginForm.submit();
            }, 2000);
        });
    }

    document.querySelectorAll("[data-logout-link]").forEach((logoutLink) => {
        logoutLink.addEventListener("click", (event) => {
            if (logoutLink.dataset.logoutDelayComplete === "true") {
                return;
            }

            const logoutLoading = document.querySelector("[data-logout-loading]");

            if (logoutLoading) {
                event.preventDefault();
                logoutLoading.setAttribute("aria-hidden", "false");
                document.body.classList.add("is-session-loading");

                window.setTimeout(() => {
                    logoutLink.dataset.logoutDelayComplete = "true";
                    window.location.href = logoutLink.href;
                }, 1000);
            }
        });
    });

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
            if (window.matchMedia("(max-width: 900px)").matches) {
                document.body.classList.toggle("admin-sidebar-open");
                return;
            }

            document.body.classList.toggle("admin-sidebar-collapsed");
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
            const hasSelectedChoice = choices.some((choice) => choice.value === fieldValue);

            if (fieldValue && !hasSelectedChoice) {
                const option = document.createElement("option");
                option.value = "";
                option.textContent = "Select case status";
                option.selected = true;
                field.appendChild(option);
            }

            choices.forEach((choice) => {
                const option = document.createElement("option");
                option.value = choice.value;
                option.textContent = choice.label;
                option.selected = choice.value === fieldValue;
                field.appendChild(option);
            });
        } else if (options.type === "choice-boxes") {
            field = document.createElement("div");
            field.className = "case-choice-boxes";
            field.setAttribute("role", "radiogroup");

            const choices = options.choices || [];
            const normalizedFieldValue = String(fieldValue).trim().toLowerCase();

            choices.forEach((choice) => {
                const choiceLabel = document.createElement("label");
                const input = document.createElement("input");
                const labelText = document.createElement("span");

                choiceLabel.className = "case-choice-box";
                input.type = "radio";
                input.name = options.name || "";
                input.value = choice.value;
                input.checked = String(choice.value).toLowerCase() === normalizedFieldValue;
                input.disabled = !options.editable;
                labelText.textContent = choice.label;
                choiceLabel.append(input, labelText);
                field.appendChild(choiceLabel);
            });
        } else {
            field = document.createElement("input");
            field.type = options.type === "date" ? "date" : "text";
            field.value = fieldValue;
            field.readOnly = !options.editable;
        }

        if (options.name && options.type !== "choice-boxes") {
            field.name = options.name;
        }

        if (options.maxLength) {
            field.maxLength = options.maxLength;
        }

        if (options.lettersUppercase) {
            field.dataset.lettersUppercase = "";
        }

        if (options.numericOnly) {
            field.dataset.numericOnly = "";
            field.inputMode = "numeric";
            field.maxLength = options.exactDigits || options.maxLength || 11;
        }

        if (options.exactDigits) {
            field.dataset.exactDigits = String(options.exactDigits);
        }

        if (options.ageSource) {
            field.dataset.ageSource = options.ageSource;
        }

        if (options.locked) {
            field.dataset.adminCaseDateLocked = "true";
            field.title = "This date is locked after saving.";
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

    const normalizeAdminCaseStatusValue = (status) => {
        const value = String(status || "").trim();
        const lower = value.toLowerCase();

        if (lower === "cfa" || lower === "cfa (call for action)" || lower === "call for action" || lower === "cfa (certificate to file action)" || lower === "certificate to file action" || lower === "cfa (certificate of file action)" || lower === "certificate of file action") {
            return "CFA";
        }

        if (lower === "m" || lower === "mediation") {
            return "M";
        }

        if (lower === "c" || lower === "conciliation" || lower === "for conciliation stage") {
            return "C";
        }

        return value;
    };

    let adminCaseValidationMessageId = 0;
    const adminCaseFieldLabels = {
        case_title: "Case Title",
        complainant_title: "Complainant Title",
        complainant_full_name: "Complainant Full Name",
        complainant_address: "Complainant Address",
        complainant_status: "Complainant Status",
        complainant_religion: "Complainant Religion",
        complainant_birthdate: "Complainant Birthdate",
        complainant_age: "Complainant Age",
        complainant_government_id: "Complainant Government ID",
        complainant_contact_number: "Complainant Contact Number",
        respondent_full_name: "Respondent Full Name",
        respondent_address: "Respondent Address",
        respondent_contact_number: "Respondent Contact Number",
    };
    const adminRequiredPartyFields = Object.keys(adminCaseFieldLabels).filter((name) => name !== "complainant_age");
    const adminLettersOnlyFields = ["case_title", "complainant_title", "complainant_full_name", "complainant_religion", "respondent_full_name"];
    const adminExactDigitFields = {
        complainant_contact_number: 11,
        respondent_contact_number: 11,
    };
    const adminValidComplainantStatuses = new Set(["Single", "Married", "Widowed", "Separated"]);
    const adminComplainantStatusChoices = [
        { value: "", label: "Select status" },
        { value: "Single", label: "Single" },
        { value: "Married", label: "Married" },
        { value: "Widowed", label: "Widowed" },
        { value: "Separated", label: "Separated" },
    ];
    const sanitizeAdminLettersUppercase = (value) => String(value || "").replace(/[^\p{L}\s]/gu, "").replace(/\s{2,}/g, " ").toUpperCase();
    const sanitizeAdminDigits = (value, maxLength = 11) => String(value || "").replace(/\D/g, "").slice(0, maxLength);
    const hasAdminFourDigitDateYear = (value) => /^\d{4}-\d{2}-\d{2}$/.test(String(value || ""));
    const isAdminLettersOnly = (value) => /^[\p{L}\s]+$/u.test(String(value || "").trim());
    const calculateAdminAgeFromBirthdate = (birthdate) => {
        if (!birthdate) {
            return "";
        }

        const birth = new Date(`${birthdate}T00:00:00`);
        const today = new Date();

        if (Number.isNaN(birth.getTime()) || birth > today) {
            return "";
        }

        let age = today.getFullYear() - birth.getFullYear();
        const monthDelta = today.getMonth() - birth.getMonth();

        if (monthDelta < 0 || (monthDelta === 0 && today.getDate() < birth.getDate())) {
            age -= 1;
        }

        return age >= 0 ? String(age) : "";
    };
    const applyAdminCaseInputSanitizers = (form) => {
        adminLettersOnlyFields.forEach((name) => {
            const field = form.elements[name];

            if (field && !field.readOnly && !field.disabled) {
                field.value = sanitizeAdminLettersUppercase(field.value);
            }
        });

        Object.entries(adminExactDigitFields).forEach(([name, length]) => {
            const field = form.elements[name];

            if (field && !field.readOnly && !field.disabled) {
                field.value = sanitizeAdminDigits(field.value, length);
            }
        });
    };

    const normalizeAdminCaseStatusKey = (status) => {
        const value = String(status || "").trim().toLowerCase();

        if (value === "cfa" || value === "cfa (call for action)" || value === "call for action" || value === "cfa (certificate to file action)" || value === "certificate to file action" || value === "cfa (certificate of file action)" || value === "certificate of file action") {
            return "cfa";
        }

        return value;
    };

    const getAdminCaseFieldValue = (form, name) => String(form.elements[name]?.value || "").trim();

    const ensureAdminCaseFieldMessage = (field) => {
        const group = field?.closest(".form-group");
        const name = field?.name || "";
        let message = group?.querySelector(`[data-admin-case-field-message="${name}"]`);

        if (!group || !name) {
            return null;
        }

        if (!message) {
            message = document.createElement("p");
            message.className = "case-field-message";
            message.dataset.adminCaseFieldMessage = name;
            message.id = `admin-case-field-message-${++adminCaseValidationMessageId}`;
            message.hidden = true;
            group.appendChild(message);
            field.setAttribute("aria-describedby", message.id);
        }

        return message;
    };

    const clearAdminCaseFieldErrors = (form) => {
        form.querySelectorAll(".is-invalid").forEach((field) => {
            field.classList.remove("is-invalid");
        });

        form.querySelectorAll("[aria-invalid='true']").forEach((field) => {
            field.removeAttribute("aria-invalid");
        });

        form.querySelectorAll("[data-admin-case-field-message]").forEach((message) => {
            message.textContent = "";
            message.hidden = true;
        });
    };

    const setAdminCaseFieldError = (field, messageText) => {
        const message = ensureAdminCaseFieldMessage(field);

        if (!field) {
            return;
        }

        field.classList.add("is-invalid");
        field.setAttribute("aria-invalid", "true");

        if (message && messageText) {
            message.textContent = messageText;
            message.hidden = false;
        }
    };

    const addAdminCaseValidationError = (form, errorsByName, name, message) => {
        if (errorsByName.has(name)) {
            return;
        }

        const field = form.elements[name];

        if (!field) {
            return;
        }

        errorsByName.set(name, message);
        setAdminCaseFieldError(field, message);
    };

    const getAdminInitialConfrontationDependencyMessage = (dateFiled) => {
        if (!dateFiled) {
            return "Please enter Date Filed first.";
        }

        return "";
    };

    const getAdminSettlementDependencyMessage = (dateFiled, initialConfrontation) => {
        if (!dateFiled && !initialConfrontation) {
            return "Please enter Date Filed and Initial Confrontation Date first.";
        }

        if (!dateFiled) {
            return "Please enter Date Filed first.";
        }

        if (!initialConfrontation) {
            return "Initial Confrontation Date is required before settlement date.";
        }

        return "";
    };

    const getAdminExecutionDependencyMessage = (dateFiled, initialConfrontation, settlementAward) => {
        if (!dateFiled && !initialConfrontation && !settlementAward) {
            return "Please enter Date Filed, Initial Confrontation Date, and Settlement Date first.";
        }

        if (!dateFiled) {
            return "Please enter Date Filed first.";
        }

        if (!initialConfrontation) {
            return "Initial Confrontation Date is required before execution date.";
        }

        if (!settlementAward) {
            return "Settlement date is required before execution date.";
        }

        return "";
    };

    const validateAdminCaseRules = (form) => {
        applyAdminCaseInputSanitizers(form);

        const errorsByName = new Map();
        const dateFiled = getAdminCaseFieldValue(form, "date_filed");
        const initialConfrontation = getAdminCaseFieldValue(form, "date_initial_confrontation");
        const settlementAward = getAdminCaseFieldValue(form, "date_settlement_award");
        const executionDate = getAdminCaseFieldValue(form, "date_execution");
        const agreement = getAdminCaseFieldValue(form, "main_point_of_agreement");
        const status = normalizeAdminCaseStatusKey(getAdminCaseFieldValue(form, "case_status"));
        const complainantBirthdate = getAdminCaseFieldValue(form, "complainant_birthdate");
        const calculatedComplainantAge = calculateAdminAgeFromBirthdate(complainantBirthdate);

        if (form.elements.complainant_age && calculatedComplainantAge !== "") {
            form.elements.complainant_age.value = calculatedComplainantAge;
        }

        adminRequiredPartyFields.forEach((name) => {
            if (getAdminCaseFieldValue(form, name) === "") {
                addAdminCaseValidationError(form, errorsByName, name, `${adminCaseFieldLabels[name]} is required.`);
            }
        });

        adminLettersOnlyFields.forEach((name) => {
            const value = getAdminCaseFieldValue(form, name);

            if (value !== "" && !isAdminLettersOnly(value)) {
                addAdminCaseValidationError(form, errorsByName, name, `${adminCaseFieldLabels[name]} must contain letters only.`);
            }
        });

        if (getAdminCaseFieldValue(form, "complainant_status") !== "" && !adminValidComplainantStatuses.has(getAdminCaseFieldValue(form, "complainant_status"))) {
            addAdminCaseValidationError(form, errorsByName, "complainant_status", "Complainant Status must be Single, Married, Widowed, or Separated.");
        }

        if (complainantBirthdate && !hasAdminFourDigitDateYear(complainantBirthdate)) {
            addAdminCaseValidationError(form, errorsByName, "complainant_birthdate", "Complainant Birthdate year must be exactly 4 digits.");
        }

        if (complainantBirthdate && calculatedComplainantAge === "") {
            addAdminCaseValidationError(form, errorsByName, "complainant_birthdate", "Complainant Birthdate must be a valid past date.");
        }

        Object.entries(adminExactDigitFields).forEach(([name, length]) => {
            const value = getAdminCaseFieldValue(form, name);

            if (value !== "" && !new RegExp(`^\\d{${length}}$`).test(value)) {
                addAdminCaseValidationError(form, errorsByName, name, `${adminCaseFieldLabels[name]} must be exactly ${length} digits.`);
            }
        });

        if (!status) {
            addAdminCaseValidationError(form, errorsByName, "case_status", "Select a valid case status.");
        }

        if (settlementAward) {
            const message = getAdminSettlementDependencyMessage(dateFiled, initialConfrontation);

            if (message) {
                addAdminCaseValidationError(form, errorsByName, "date_settlement_award", message);
            }
        }

        if (executionDate) {
            const message = getAdminExecutionDependencyMessage(dateFiled, initialConfrontation, settlementAward);

            if (message) {
                addAdminCaseValidationError(form, errorsByName, "date_execution", message);
            }
        }

        if ((status === "settled" || settlementAward) && !agreement) {
            addAdminCaseValidationError(form, errorsByName, "main_point_of_agreement", "Main Point of Agreement is required for settled cases.");
        }

        if (status === "endorsed") {
            if (settlementAward || executionDate) {
                addAdminCaseValidationError(form, errorsByName, "case_status", "Endorsed cases must not have settlement or execution dates.");
            }

            if (!agreement) {
                addAdminCaseValidationError(form, errorsByName, "main_point_of_agreement", "Main Point of Agreement is required for endorsed cases.");
            }
        }

        if (status === "dismissed") {
            if (settlementAward || executionDate) {
                addAdminCaseValidationError(form, errorsByName, "case_status", "Dismissed cases must not have settlement or execution dates.");
            }

            if (!agreement) {
                addAdminCaseValidationError(form, errorsByName, "main_point_of_agreement", "Dismissal reason is required.");
            }
        }

        if (status === "cfa") {
            if (settlementAward || executionDate) {
                addAdminCaseValidationError(form, errorsByName, "case_status", "CFA cases must not have settlement or execution dates.");
            }

            if (!agreement) {
                addAdminCaseValidationError(form, errorsByName, "main_point_of_agreement", "Main Point of Agreement is required for CFA cases.");
            }
        }

        return Array.from(errorsByName.values());
    };

    const setAdminCaseDateFieldDisabled = (field, disabled, message = "") => {
        if (!field || field.dataset.adminCaseDateLocked === "true") {
            return;
        }

        field.disabled = false;
        field.readOnly = disabled;
        field.title = disabled ? message : "";
        field.dataset.adminCaseDateBlocked = disabled ? "true" : "false";
        field.setAttribute("aria-disabled", disabled ? "true" : "false");
    };

    const updateAdminCaseDateFieldAvailability = (form) => {
        const initialField = form.elements.date_initial_confrontation;
        const settlementField = form.elements.date_settlement_award;
        const executionField = form.elements.date_execution;
        const dateFiled = getAdminCaseFieldValue(form, "date_filed");
        let initialConfrontation = getAdminCaseFieldValue(form, "date_initial_confrontation");
        let settlementAward = getAdminCaseFieldValue(form, "date_settlement_award");

        if (!dateFiled && initialField && initialField.dataset.adminCaseDateLocked !== "true" && initialField.value) {
            initialField.value = "";
            initialConfrontation = "";
        }

        if ((!dateFiled || !initialConfrontation) && settlementField && settlementField.dataset.adminCaseDateLocked !== "true" && settlementField.value) {
            settlementField.value = "";
            settlementAward = "";
        }

        if ((!dateFiled || !initialConfrontation || !settlementAward) && executionField && executionField.dataset.adminCaseDateLocked !== "true" && executionField.value) {
            executionField.value = "";
        }

        setAdminCaseDateFieldDisabled(initialField, !dateFiled, getAdminInitialConfrontationDependencyMessage(dateFiled));
        setAdminCaseDateFieldDisabled(settlementField, !dateFiled || !initialConfrontation, getAdminSettlementDependencyMessage(dateFiled, initialConfrontation));
        setAdminCaseDateFieldDisabled(executionField, !dateFiled || !initialConfrontation || !settlementAward, getAdminExecutionDependencyMessage(dateFiled, initialConfrontation, settlementAward));
    };

    const showBlockedAdminCaseDateMessage = (form, name) => {
        const field = form.elements[name];
        const dateFiled = getAdminCaseFieldValue(form, "date_filed");
        const initialConfrontation = getAdminCaseFieldValue(form, "date_initial_confrontation");
        const settlementAward = getAdminCaseFieldValue(form, "date_settlement_award");
        let message = "";

        if (!field || field.dataset.adminCaseDateLocked === "true") {
            return false;
        }

        if (name === "date_initial_confrontation") {
            message = getAdminInitialConfrontationDependencyMessage(dateFiled);
        } else if (name === "date_settlement_award") {
            message = getAdminSettlementDependencyMessage(dateFiled, initialConfrontation);
        } else if (name === "date_execution") {
            message = getAdminExecutionDependencyMessage(dateFiled, initialConfrontation, settlementAward);
        }

        if (!message) {
            return false;
        }

        setAdminCaseFieldError(field, message);
        return true;
    };

    const preventBlockedAdminCaseDateEntry = (form, field, event = null) => {
        if (field?.dataset.adminCaseDateBlocked !== "true") {
            return false;
        }

        event?.preventDefault();
        showBlockedAdminCaseDateMessage(form, field.name);
        return true;
    };

    const renderAdminCaseDetails = (body, caseData, apiUrl = "cases_api.php") => {
        const form = document.createElement("form");
        const grid = document.createElement("div");
        const feedback = document.createElement("p");
        const actions = document.createElement("div");
        const printLink = document.createElement("a");
        const saveButton = document.createElement("button");
        const caseStatusValue = normalizeAdminCaseStatusValue(caseData.case_status);
        const hasInitialConfrontation = Boolean(String(caseData.date_initial_confrontation || "").trim());
        const hasSettlementAward = Boolean(String(caseData.date_settlement_award || "").trim());
        const hasExecutionDate = Boolean(String(caseData.date_execution || "").trim());

        const statusChoices = [
            { value: "M", label: "M" },
            { value: "C", label: "C" },
            { value: "CFA", label: "CFA" },
            { value: "Endorsed", label: "Endorsed" },
            { value: "Dismissed", label: "Dismissed" },
        ];
        const removedStatusValues = new Set(["settled"]);

        if (
            caseStatusValue
            && !removedStatusValues.has(String(caseStatusValue).trim().toLowerCase())
            && !statusChoices.some((choice) => choice.value === caseStatusValue)
        ) {
            statusChoices.unshift({ value: caseStatusValue, label: caseStatusValue });
        }

        form.className = "case-form case-details-form admin-edit-case-form";
        form.setAttribute("aria-label", "Editable case details");
        grid.className = "case-form-grid";
        feedback.className = "admin-case-save-message";
        feedback.hidden = true;
        actions.className = "admin-case-modal-actions";
        printLink.className = "admin-primary-button compact";
        printLink.href = `print_case.php?id=${encodeURIComponent(caseData.id || "")}`;
        printLink.textContent = "Print Case";
        saveButton.className = "admin-primary-button compact";
        saveButton.type = "submit";
        saveButton.textContent = "Save Changes";

        grid.append(
            createAdminCaseDetailSection(
                "Case Identification",
                "Basic filing details used to classify and locate the case record.",
                [
                    createAdminCaseDetailField("Case Number", caseData.case_number),
                    createAdminCaseDetailField("Case Title", caseData.case_title, { name: "case_title", editable: false, wide: true }),
                    createAdminCaseDetailField("Complainant Title", caseData.complainant_title, { name: "complainant_title", editable: false }),
                    createAdminCaseDetailField("Nature of Case", caseData.nature_of_case, {
                        type: "choice-boxes",
                        name: "nature_of_case",
                        editable: false,
                        choices: [
                            { value: "Civil", label: "Civil" },
                            { value: "Criminal", label: "Criminal" },
                        ],
                    }),
                ]
            ),
            createAdminCaseDetailSection(
                "Complainant Information",
                "Additional personal details for the complainant record.",
                [
                    createAdminCaseDetailField("Full Name", caseData.complainant_full_name),
                    createAdminCaseDetailField("Address", caseData.complainant_address, { wide: true }),
                    createAdminCaseDetailField("Status", caseData.complainant_status),
                    createAdminCaseDetailField("Religion", caseData.complainant_religion),
                    createAdminCaseDetailField("Birthdate", caseData.complainant_birthdate, { type: "date" }),
                    createAdminCaseDetailField("Age", caseData.complainant_age),
                    createAdminCaseDetailField("Government ID", caseData.complainant_government_id),
                    createAdminCaseDetailField("Contact Number", caseData.complainant_contact_number),
                ]
            ),
            createAdminCaseDetailSection(
                "Respondent Information",
                "Additional contact details for the respondent record.",
                [
                    createAdminCaseDetailField("Full Name", caseData.respondent_full_name),
                    createAdminCaseDetailField("Contact Number", caseData.respondent_contact_number),
                    createAdminCaseDetailField("Address", caseData.respondent_address, { wide: true }),
                ]
            ),
            createAdminCaseDetailSection(
                "Schedule and Status",
                "Filing dates, case movement, and current case status.",
                [
                    createAdminCaseDetailField("Date Filed", caseData.date_filed, { type: "date", name: "date_filed", editable: false, locked: true }),
                    createAdminCaseDetailField("Date of Initial Confrontation", caseData.date_initial_confrontation, { type: "date", name: "date_initial_confrontation", editable: !hasInitialConfrontation, locked: hasInitialConfrontation }),
                    createAdminCaseDetailField("Case Status", caseStatusValue, { type: "select", name: "case_status", editable: true, choices: statusChoices }),
                    createAdminCaseDetailField("Date of Settlement / Award", caseData.date_settlement_award, { type: "date", name: "date_settlement_award", editable: !hasSettlementAward, locked: hasSettlementAward }),
                    createAdminCaseDetailField("Date of Execution", caseData.date_execution, { type: "date", name: "date_execution", editable: !hasExecutionDate, locked: hasExecutionDate }),
                ],
                "date-status-grid"
            ),
            createAdminCaseDetailSection(
                "Case Narrative",
                "Documented incident details and agreement reached during proceedings.",
                [
                    createAdminCaseDetailField("Detailed Case Description", caseData.detailed_case_description, { type: "textarea", name: "detailed_case_description", editable: false, wide: true }),
                    createAdminCaseDetailField("Main Point of Agreement", caseData.main_point_of_agreement, { type: "textarea", name: "main_point_of_agreement", editable: true, wide: true }),
                ],
                "section-grid narrative-grid"
            )
        );

        actions.append(printLink, saveButton);
        form.append(grid, feedback, actions);

        const validateLiveAdminCaseForm = () => {
            clearAdminCaseFieldErrors(form);
            updateAdminCaseDateFieldAvailability(form);
            validateAdminCaseRules(form);
        };

        ["date_initial_confrontation", "date_settlement_award", "date_execution", "case_status", "main_point_of_agreement"].forEach((name) => {
            const field = form.elements[name];

            if (!field) {
                return;
            }

            const validateField = () => {
                clearAdminCaseFieldErrors(form);
                if (field.dataset.adminCaseDateBlocked === "true") {
                    field.value = "";
                    showBlockedAdminCaseDateMessage(form, name);
                    return;
                }

                updateAdminCaseDateFieldAvailability(form);
                validateAdminCaseRules(form);
            };

            field.addEventListener("input", validateField);
            field.addEventListener("change", validateField);
            field.addEventListener("mousedown", (event) => {
                preventBlockedAdminCaseDateEntry(form, field, event);
            });
            field.addEventListener("keydown", (event) => {
                preventBlockedAdminCaseDateEntry(form, field, event);
            });
            field.addEventListener("focus", () => {
                if (preventBlockedAdminCaseDateEntry(form, field)) {
                    return;
                }

                validateField();
            });

            field.closest(".form-group")?.addEventListener("click", () => {
                if (field.dataset.adminCaseDateBlocked === "true") {
                    showBlockedAdminCaseDateMessage(form, name);
                }
            });
        });

        updateAdminCaseDateFieldAvailability(form);

        form.addEventListener("submit", async (event) => {
            event.preventDefault();
            clearAdminCaseFieldErrors(form);
            updateAdminCaseDateFieldAvailability(form);
            const validationErrors = validateAdminCaseRules(form);

            if (validationErrors.length > 0) {
                feedback.textContent = "Please resolve validation messages before saving.";
                feedback.classList.remove("is-success");
                feedback.classList.add("is-error");
                feedback.hidden = false;
                return;
            }

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

    const caseResultSelectors = ".admin-table-wrap, .cases-pagination";

    const setAdminCaseResultsLoading = (panel, isLoading) => {
        panel.querySelectorAll(caseResultSelectors).forEach((element) => {
            element.classList.toggle("is-refreshing", isLoading);
        });
    };

    const buildAdminCaseResultsUrl = (form, sourceUrl = null) => {
        const panel = form.closest(".cases-panel");
        const baseUrl = new URL(sourceUrl || panel?.dataset.caseListUrl || window.location.href, window.location.href);
        const actionUrl = new URL(form.action || window.location.href, window.location.href);
        const params = new URLSearchParams(baseUrl.search);

        new FormData(form).forEach((value, key) => {
            const nextValue = String(value).trim();

            if (nextValue === "") {
                params.delete(key);
                return;
            }

            params.set(key, nextValue);
        });

        baseUrl.pathname = actionUrl.pathname;
        baseUrl.search = params.toString();
        baseUrl.hash = "";

        return baseUrl;
    };

    const refreshAdminCaseResults = async (form, sourceUrl = null) => {
        const panel = form.closest(".cases-panel");

        if (!panel) {
            form.submit();
            return;
        }

        const nextUrl = buildAdminCaseResultsUrl(form, sourceUrl);

        if (panel.caseResultsAbortController) {
            panel.caseResultsAbortController.abort();
        }

        const abortController = new AbortController();
        panel.caseResultsAbortController = abortController;
        setAdminCaseResultsLoading(panel, true);

        try {
            const response = await fetch(nextUrl, {
                headers: {
                    Accept: "text/html",
                    "X-Requested-With": "XMLHttpRequest",
                },
                signal: abortController.signal,
            });

            if (!response.ok) {
                throw new Error("Unable to load cases.");
            }

            const html = await response.text();
            const nextDocument = new DOMParser().parseFromString(html, "text/html");
            const nextTable = nextDocument.querySelector(".admin-table-wrap");
            const nextPagination = nextDocument.querySelector(".cases-pagination");
            const currentTable = panel.querySelector(".admin-table-wrap");
            const currentPagination = panel.querySelector(".cases-pagination");

            if (!nextTable || !nextPagination || !currentTable || !currentPagination) {
                throw new Error("Case results were not found.");
            }

            currentTable.replaceWith(nextTable);
            currentPagination.replaceWith(nextPagination);
            panel.dataset.caseListUrl = `${nextUrl.pathname}${nextUrl.search}`;

            updateCaseFilterCards(nextUrl.searchParams.get("status") || "");
            initializeAdminCaseRows(panel);
            initializeAdminCasePagination(panel);
        } catch (error) {
            if (error.name !== "AbortError") {
                window.location.href = nextUrl;
            }
        } finally {
            if (panel.caseResultsAbortController === abortController) {
                panel.caseResultsAbortController = null;
                setAdminCaseResultsLoading(panel, false);
            }
        }
    };

    const renderCaseImportSummary = (summary, payload, titleText) => {
        const imported = Number(payload.imported || 0);
        const skipped = Array.isArray(payload.skipped) ? payload.skipped : [];
        const errors = Array.isArray(payload.errors) ? payload.errors : [];
        const detailItems = [
            ...skipped.map((item) => ({
                row: item.row,
                reason: item.reason || `Skipped duplicate case number: ${item.case_number || ""}`,
            })),
            ...errors.map((item) => ({
                row: item.row,
                reason: item.reason || "Invalid record.",
            })),
        ];
        const detailHtml = detailItems.length
            ? `<ul class="case-import-detail-list">${detailItems.slice(0, 30).map((item) => {
                const rowLabel = Number(item.row || 0) > 0 ? `Row ${escapeAdminHtml(item.row)}: ` : "";
                return `<li>${rowLabel}${escapeAdminHtml(item.reason)}</li>`;
            }).join("")}</ul>`
            : "";

        summary.innerHTML = `
            <h4>${escapeAdminHtml(titleText)}</h4>
            <dl>
                <dt>Successfully Imported:</dt>
                <dd>${escapeAdminHtml(imported)} ${imported === 1 ? "case" : "cases"}</dd>
                <dt>Skipped:</dt>
                <dd>${escapeAdminHtml(skipped.length)} ${skipped.length === 1 ? "duplicate record" : "duplicate records"}</dd>
                <dt>Errors:</dt>
                <dd>${escapeAdminHtml(errors.length)} ${errors.length === 1 ? "invalid record" : "invalid records"}</dd>
            </dl>
            ${detailHtml}
        `;
        summary.hidden = false;
    };

    const initializeCaseImport = () => {
        const modal = document.querySelector("[data-case-import-modal]");
        const openButton = document.querySelector("[data-open-case-import]");

        if (!modal || !openButton || modal.dataset.caseImportReady === "true") {
            return;
        }

        modal.dataset.caseImportReady = "true";
        const form = modal.querySelector("[data-case-import-form]");
        const fileInput = modal.querySelector("[data-case-import-file]");
        const fileGroup = modal.querySelector("[data-case-import-file-group]");
        const errorBox = modal.querySelector("[data-case-import-error]");
        const summary = modal.querySelector("[data-case-import-summary]");
        const submitButton = modal.querySelector("[data-case-import-submit]");
        const okButton = modal.querySelector("[data-case-import-ok]");
        const cancelButton = modal.querySelector("[data-case-import-cancel]");
        const title = modal.querySelector("[data-case-import-title]");
        const subtitle = modal.querySelector("[data-case-import-subtitle]");

        const setError = (message) => {
            errorBox.textContent = message;
            errorBox.hidden = false;
        };

        const clearError = () => {
            errorBox.textContent = "";
            errorBox.hidden = true;
        };

        const resetImportModal = () => {
            form.reset();
            clearError();
            summary.hidden = true;
            summary.innerHTML = "";
            fileGroup.hidden = false;
            submitButton.hidden = false;
            submitButton.disabled = false;
            submitButton.textContent = "Upload";
            cancelButton.hidden = false;
            okButton.hidden = true;
            modal.dataset.reloadOnClose = "false";
            title.textContent = "Import Excel";
            subtitle.textContent = "Select an Excel file containing case records.";
        };

        const openModal = () => {
            resetImportModal();
            modal.hidden = false;
            document.body.classList.add("admin-modal-open");
            window.setTimeout(() => fileInput.focus(), 0);
        };

        const closeModal = () => {
            const shouldReload = modal.dataset.reloadOnClose === "true";
            modal.hidden = true;
            document.body.classList.remove("admin-modal-open");

            if (shouldReload) {
                window.location.reload();
            }
        };

        openButton.addEventListener("click", openModal);
        modal.querySelectorAll("[data-close-case-import]").forEach((button) => {
            button.addEventListener("click", closeModal);
        });
        okButton.addEventListener("click", closeModal);

        document.addEventListener("keydown", (event) => {
            if (!modal.hidden && event.key === "Escape") {
                event.preventDefault();
                closeModal();
            }
        });

        form.addEventListener("submit", async (event) => {
            event.preventDefault();
            clearError();

            if (!fileInput.files || fileInput.files.length === 0) {
                setError("Select an Excel file to upload.");
                fileInput.focus();
                return;
            }

            submitButton.disabled = true;
            submitButton.textContent = "Uploading...";

            try {
                const response = await fetch(form.action, {
                    method: "POST",
                    body: new FormData(form),
                    headers: { Accept: "application/json" },
                });
                const payload = await response.json().catch(() => ({
                    success: false,
                    message: "Import failed. Please check the uploaded file.",
                    imported: 0,
                    skipped: [],
                    errors: [{ row: 0, reason: "The server returned an invalid response." }],
                }));
                const imported = Number(payload.imported || 0);
                const titleText = imported > 0 ? "Import Completed" : "Import Failed";

                if (!response.ok && (!payload.errors || payload.errors.length === 0)) {
                    throw new Error(payload.message || "Import failed. Please check the uploaded file.");
                }

                title.textContent = titleText;
                subtitle.textContent = payload.message || "Import failed. Please check the uploaded file.";
                fileGroup.hidden = true;
                submitButton.hidden = true;
                cancelButton.hidden = true;
                okButton.hidden = false;
                modal.dataset.reloadOnClose = imported > 0 ? "true" : "false";
                renderCaseImportSummary(summary, payload, titleText);
                okButton.focus();
            } catch (error) {
                setError(error.message || "Import failed. Please check the uploaded file.");
                submitButton.disabled = false;
                submitButton.textContent = "Upload";
            }
        });
    };

    const initializeAdminCaseFilters = (root = document) => {
        root.querySelectorAll("[data-admin-case-filters]").forEach((form) => {
            if (form.dataset.caseFiltersReady === "true") {
                return;
            }

            form.dataset.caseFiltersReady = "true";
            const panel = form.closest(".cases-panel");

            if (panel && !panel.dataset.caseListUrl) {
                panel.dataset.caseListUrl = `${window.location.pathname}${window.location.search}`;
            }

            const searchInputs = form.querySelectorAll("[data-admin-case-search]");
            let searchTimer = null;

            if (searchInputs.length > 0) {
                searchInputs.forEach((searchInput) => {
                    searchInput.addEventListener("input", () => {
                        window.clearTimeout(searchTimer);
                        searchTimer = window.setTimeout(() => refreshAdminCaseResults(form), 180);
                    });
                });
            }

            form.querySelectorAll("[data-admin-case-filter]").forEach((filter) => {
                filter.addEventListener("change", () => refreshAdminCaseResults(form));
            });

            form.querySelectorAll("[data-admin-case-date]").forEach((dateInput) => {
                dateInput.addEventListener("input", () => {
                    const dateField = form.querySelector("[name='date_filter']");

                    if (dateInput.value.trim() !== "" && dateField && dateField.value === "") {
                        dateField.value = "date_filed";
                    }

                    window.clearTimeout(searchTimer);
                    searchTimer = window.setTimeout(() => refreshAdminCaseResults(form), 180);
                });
            });

            form.addEventListener("submit", (event) => {
                if (event.submitter?.classList.contains("export-button")) {
                    return;
                }

                event.preventDefault();
                refreshAdminCaseResults(form);
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

    const initializeAdminCasePagination = (root = document) => {
        root.querySelectorAll("[data-case-page-url]").forEach((button) => {
            if (button.dataset.casePaginationReady === "true") {
                return;
            }

            button.dataset.casePaginationReady = "true";
            button.addEventListener("click", (event) => {
                const form = document.querySelector("[data-admin-case-filters]");

                if (!form) {
                    return;
                }

                event.preventDefault();
                event.stopImmediatePropagation();
                refreshAdminCaseResults(form, button.dataset.casePageUrl || null);
            }, true);
        });
    };

    const updateCaseFilterCards = (status) => {
        const form = document.querySelector("[data-admin-case-filters]");
        const hasSearchFilter = Array.from(form?.querySelectorAll("[data-admin-case-search]") || []).some((input) => input.value.trim() !== "");
        const hasDateFilter = Boolean(form?.querySelector("[name='date_filter']")?.value.trim() || form?.querySelector("[name='date_value']")?.value.trim());

        document.querySelectorAll("[data-case-filter-card]").forEach((card) => {
            const cardStatus = card.dataset.caseFilterStatus || "";
            const isUnfilteredTotal = cardStatus === "" && status === "" && !hasSearchFilter && !hasDateFilter;

            card.classList.toggle("is-active", isUnfilteredTotal || (cardStatus !== "" && cardStatus === status));
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
                initializeAdminCasePagination(casesPanel);
                initializeCaseImport();
                casesPanel.dataset.caseListUrl = `${nextUrl.pathname}${nextUrl.search}`;
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
    initializeAdminCasePagination();
    initializeCaseImport();

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

    const parseInitialUsers = () => {
        try {
            const initialUsers = JSON.parse(userManagement.dataset.initialUsers || "[]");
            return Array.isArray(initialUsers) ? initialUsers : [];
        } catch (error) {
            return [];
        }
    };

    let users = parseInitialUsers();
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
