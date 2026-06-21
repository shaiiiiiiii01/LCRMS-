document.addEventListener("DOMContentLoaded", () => {
    const passwordToggle = document.querySelector("[data-toggle-password]");
    const passwordInput = document.querySelector("#password");

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

    document.querySelectorAll("[data-sidebar-toggle]").forEach((button) => {
        button.addEventListener("click", () => {
            if (window.matchMedia("(max-width: 900px)").matches) {
                document.body.classList.toggle("sidebar-open");
                return;
            }

            document.body.classList.toggle("sidebar-collapsed");
        });
    });

    document.querySelectorAll("[data-sidebar-close]").forEach((backdrop) => {
        backdrop.addEventListener("click", () => {
            document.body.classList.remove("sidebar-open");
        });
    });

    document.querySelectorAll(".add-case-card").forEach((card) => {
        card.addEventListener("click", (event) => {
            event.stopPropagation();
            document.body.classList.remove("sidebar-open");
        });
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
            document.body.classList.remove("sidebar-open");
        }
    });

    document.querySelectorAll("[data-entry-row]").forEach((row) => {
        const href = row.dataset.href;
        const caseId = row.dataset.caseId;
        const opensCaseDetails = row.hasAttribute("data-case-detail-row") && caseId;

        if (!href && !opensCaseDetails) {
            return;
        }

        row.addEventListener("click", (event) => {
            if (event.target.closest("button, input, select, textarea")) {
                return;
            }

            if (opensCaseDetails) {
                event.preventDefault();
                openCaseDetailsModal(caseId);
                return;
            }

            window.location.href = href;
        });

        row.addEventListener("keydown", (event) => {
            if (event.key === "Enter" || event.key === " ") {
                event.preventDefault();

                if (opensCaseDetails) {
                    openCaseDetailsModal(caseId);
                    return;
                }

                window.location.href = href;
            }
        });
    });

    const caseFieldLabels = {
        case_title: "Case Title",
        complainant_title: "Complainant Title",
        nature_of_case: "Nature of Case",
        date_filed: "Date Filed",
        detailed_case_description: "Detailed Case Description",
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

    const requiredCaseFields = Object.keys(caseFieldLabels);
    const lettersOnlyFields = ["case_title", "complainant_title", "complainant_full_name", "complainant_religion", "respondent_full_name"];
    const exactDigitFields = {
        complainant_contact_number: 11,
        respondent_contact_number: 11,
    };
    const validComplainantStatuses = new Set(["Single", "Married", "Widowed", "Separated"]);

    const sanitizeLettersUppercase = (value) => String(value || "").replace(/[^\p{L}\s]/gu, "").replace(/\s{2,}/g, " ").toUpperCase();
    const sanitizeDigits = (value, maxLength = 11) => String(value || "").replace(/\D/g, "").slice(0, maxLength);
    const hasFourDigitDateYear = (value) => /^\d{4}-\d{2}-\d{2}$/.test(String(value || ""));
    const isLettersOnly = (value) => /^[\p{L}\s]+$/u.test(String(value || "").trim());
    const markRequiredCaseLabels = (root = document) => {
        requiredCaseFields.forEach((name) => {
            const field = root.querySelector(`[name="${name}"]`);
            const label = field?.closest(".form-group")?.querySelector("label");

            label?.classList.add("is-required");
        });
    };

    markRequiredCaseLabels();

    const applyCaseInputSanitizers = (form) => {
        lettersOnlyFields.forEach((name) => {
            const field = form.elements[name];

            if (field && !field.readOnly && !field.disabled) {
                field.value = sanitizeLettersUppercase(field.value);
            }
        });

        Object.entries(exactDigitFields).forEach(([name, length]) => {
            const field = form.elements[name];

            if (field && !field.readOnly && !field.disabled) {
                field.value = sanitizeDigits(field.value, length);
            }
        });
    };

    const showCaseModal = (type, title, message, details = []) => {
        const existingModal = document.querySelector("[data-case-modal]");
        const overlay = document.createElement("div");
        const dialog = document.createElement("div");
        const icon = document.createElement("span");
        const content = document.createElement("div");
        const heading = document.createElement("strong");
        const copy = document.createElement("p");
        const actions = document.createElement("div");
        const okButton = document.createElement("button");

        if (existingModal) {
            existingModal.remove();
        }

        overlay.className = "case-modal-overlay";
        overlay.dataset.caseModal = "";
        dialog.className = `case-modal case-modal-${type}`;
        dialog.setAttribute("role", "dialog");
        dialog.setAttribute("aria-modal", "true");
        dialog.setAttribute("aria-labelledby", "caseModalTitle");
        dialog.setAttribute("aria-describedby", "caseModalMessage");
        icon.className = "case-modal-icon";
        icon.textContent = type === "success" ? "OK" : "!";
        content.className = "case-modal-content";
        heading.id = "caseModalTitle";
        heading.textContent = title;
        copy.id = "caseModalMessage";
        copy.textContent = message;
        actions.className = "case-modal-actions";
        okButton.className = "primary-button compact";
        okButton.type = "button";
        okButton.textContent = "OK";

        content.append(heading, copy);

        if (details.length > 0) {
            const list = document.createElement("ul");

            details.forEach((detail) => {
                const item = document.createElement("li");
                item.textContent = detail;
                list.appendChild(item);
            });

            content.appendChild(list);
        }

        actions.appendChild(okButton);
        dialog.append(icon, content, actions);
        overlay.appendChild(dialog);
        document.body.appendChild(overlay);
        document.body.classList.add("case-modal-open");

        const closeModal = () => {
            overlay.remove();
            document.body.classList.remove("case-modal-open");
            document.removeEventListener("keydown", handleModalKeydown);
        };

        const handleModalKeydown = (event) => {
            if (event.key === "Escape" || event.key === "Enter") {
                event.preventDefault();
                closeModal();
            }
        };

        okButton.addEventListener("click", closeModal);
        overlay.addEventListener("click", (event) => {
            if (event.target === overlay) {
                closeModal();
            }
        });
        document.addEventListener("keydown", handleModalKeydown);
        okButton.focus();
    };

    const createCaseDetailField = (labelText, value, options = {}) => {
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
            field.readOnly = true;
        } else if (options.type === "select") {
            field = document.createElement("select");
            field.disabled = true;

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
        } else {
            field = document.createElement("input");
            field.type = "text";
            field.value = fieldValue;
            field.readOnly = true;
        }

        group.append(label, field);

        return group;
    };

    const createCaseDetailSection = (title, copy, fields, gridClass = "section-grid") => {
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

    const normalizeCaseStatusValue = (status) => {
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

    const renderCaseDetails = (body, caseData) => {
        const form = document.createElement("form");
        const grid = document.createElement("div");
        const actions = document.createElement("div");
        const textActions = document.createElement("div");
        const printLink = document.createElement("a");
        const caseStatusValue = normalizeCaseStatusValue(caseData.case_status);
        const statusChoices = [
            { value: "M", label: "M" },
            { value: "C", label: "C" },
            { value: "CFA", label: "CFA" },
            { value: "Endorsed", label: "Endorsed" },
            { value: "Dismissed", label: "Dismissed" },
        ];

        form.className = "case-form readonly-case-form case-details-form";
        form.setAttribute("aria-label", "Read-only case details");
        grid.className = "case-form-grid";
        actions.className = "case-form-actions";
        textActions.className = "text-actions";
        printLink.className = "primary-button compact";
        printLink.href = `print_case.php?id=${encodeURIComponent(caseData.id || "")}`;
        printLink.textContent = "Print Case";

        grid.append(
            createCaseDetailSection(
                "Case Identification",
                "Basic filing details used to classify and locate the case record.",
                [
                    createCaseDetailField("Case Number", caseData.case_number),
                    createCaseDetailField("Case Title", caseData.case_title, { wide: true }),
                    createCaseDetailField("Complainant Title", caseData.complainant_title),
                    createCaseDetailField("Nature of Case", caseData.nature_of_case),
                ]
            ),
            createCaseDetailSection(
                "Schedule and Status",
                "Filing dates, case movement, and current case status.",
                [
                    createCaseDetailField("Date Filed", caseData.date_filed),
                    createCaseDetailField("Date of Initial Confrontation", caseData.date_initial_confrontation),
                    createCaseDetailField("Case Status", caseStatusValue, { type: "select", choices: statusChoices }),
                    createCaseDetailField("Date of Settlement / Award", caseData.date_settlement_award),
                    createCaseDetailField("Date of Execution", caseData.date_execution),
                ],
                "date-status-grid"
            ),
            createCaseDetailSection(
                "Case Narrative",
                "Documented incident details and agreement reached during proceedings.",
                [
                    createCaseDetailField("Detailed Case Description", caseData.detailed_case_description, { type: "textarea", wide: true }),
                    createCaseDetailField("Main Point of Agreement", caseData.main_point_of_agreement, { type: "textarea", wide: true }),
                ],
                "section-grid narrative-grid"
            ),
            createCaseDetailSection(
                "Complainant Information",
                "Additional personal details for the complainant record.",
                [
                    createCaseDetailField("Full Name", caseData.complainant_full_name),
                    createCaseDetailField("Address", caseData.complainant_address, { wide: true }),
                    createCaseDetailField("Status", caseData.complainant_status),
                    createCaseDetailField("Religion", caseData.complainant_religion),
                    createCaseDetailField("Birthdate", caseData.complainant_birthdate),
                    createCaseDetailField("Age", caseData.complainant_age),
                    createCaseDetailField("Government ID", caseData.complainant_government_id),
                    createCaseDetailField("Contact Number", caseData.complainant_contact_number),
                ]
            ),
            createCaseDetailSection(
                "Respondent Information",
                "Additional contact details for the respondent record.",
                [
                    createCaseDetailField("Full Name", caseData.respondent_full_name),
                    createCaseDetailField("Contact Number", caseData.respondent_contact_number),
                    createCaseDetailField("Address", caseData.respondent_address, { wide: true }),
                ]
            )
        );

        actions.append(textActions, printLink);
        form.append(grid, actions);
        body.replaceChildren(form);
    };

    async function openCaseDetailsModal(caseId) {
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
        document.body.classList.add("case-modal-open");

        const closeModal = () => {
            overlay.remove();
            document.body.classList.remove("case-modal-open");
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
            const response = await fetch(`cases_api.php?action=detail&id=${encodeURIComponent(caseId)}`);
            const data = await response.json();

            if (!response.ok || !data.success || !data.case) {
                closeModal();
                showCaseModal(
                    "error",
                    "Case Details Unavailable",
                    data.message || "Unable to load case details. Please try again."
                );
                return;
            }

            renderCaseDetails(body, data.case);
        } catch (error) {
            closeModal();
            showCaseModal(
                "error",
                "Case Details Unavailable",
                error.message || "Unable to load case details. Please try again."
            );
        }
    }

    let caseValidationMessageId = 0;

    const isRadioGroupField = (field) => typeof RadioNodeList !== "undefined" && field instanceof RadioNodeList;

    const getCaseFieldValue = (field) => {
        if (!field) {
            return "";
        }

        return isRadioGroupField(field) ? field.value : String(field.value || "");
    };

    const getChoiceBoxGroup = (field) => {
        const firstField = isRadioGroupField(field) ? Array.from(field)[0] : field;
        return firstField ? firstField.closest("[data-choice-boxes]") : null;
    };

    const getCaseFieldGroup = (field) => {
        const firstField = isRadioGroupField(field) ? Array.from(field)[0] : field;
        return firstField ? firstField.closest(".form-group") : null;
    };

    const getCaseFieldName = (field) => {
        const firstField = isRadioGroupField(field) ? Array.from(field)[0] : field;
        return firstField ? firstField.name : "";
    };

    const getCaseDateShell = (field) => {
        if (isRadioGroupField(field)) {
            return null;
        }

        return field.closest(".date-field");
    };

    const setCaseCollapsibleExpanded = (section, expanded) => {
        const toggle = section?.querySelector("[data-case-collapsible-toggle]");
        const body = section?.querySelector("[data-case-collapsible-body]");

        if (!section || !toggle || !body) {
            return;
        }

        section.classList.toggle("is-collapsed", !expanded);
        toggle.setAttribute("aria-expanded", expanded ? "true" : "false");
        toggle.querySelector("span").textContent = expanded ? "Hide fields" : "Show fields";
        body.setAttribute("aria-hidden", expanded ? "false" : "true");
        body.style.maxHeight = expanded ? `${body.scrollHeight}px` : "0px";
    };

    const expandCaseCollapsibleForField = (field) => {
        const firstField = isRadioGroupField(field) ? Array.from(field)[0] : field;
        const section = firstField?.closest("[data-case-collapsible]");

        if (section) {
            setCaseCollapsibleExpanded(section, true);
        }
    };

    const calculateAgeFromBirthdate = (birthdate) => {
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

    const ensureCaseFieldMessage = (field) => {
        const group = getCaseFieldGroup(field);
        const name = getCaseFieldName(field);
        let message = group?.querySelector(`[data-case-field-message="${name}"]`);

        if (!group || !name) {
            return null;
        }

        if (!message) {
            const firstField = isRadioGroupField(field) ? Array.from(field)[0] : field;

            message = document.createElement("p");
            message.className = "case-field-message";
            message.dataset.caseFieldMessage = name;
            message.id = `case-field-message-${++caseValidationMessageId}`;
            message.hidden = true;
            group.appendChild(message);

            if (isRadioGroupField(field)) {
                Array.from(field).forEach((input) => {
                    input.setAttribute("aria-describedby", message.id);
                });
            } else if (firstField) {
                firstField.setAttribute("aria-describedby", message.id);
            }
        }

        return message;
    };

    const clearCaseFieldErrors = (form) => {
        form.querySelectorAll(".is-invalid").forEach((field) => {
            field.classList.remove("is-invalid");
        });

        form.querySelectorAll("[aria-invalid='true']").forEach((field) => {
            field.removeAttribute("aria-invalid");
        });

        form.querySelectorAll("[data-case-field-message]").forEach((message) => {
            message.textContent = "";
            message.hidden = true;
        });
    };

    const clearCaseFieldError = (field) => {
        if (!field) {
            return;
        }

        const message = ensureCaseFieldMessage(field);

        if (isRadioGroupField(field)) {
            Array.from(field).forEach((input) => input.removeAttribute("aria-invalid"));
            getChoiceBoxGroup(field)?.classList.remove("is-invalid");
        } else {
            field.classList.remove("is-invalid");
            field.removeAttribute("aria-invalid");
            getCaseDateShell(field)?.classList.remove("is-invalid");
        }

        if (message) {
            message.textContent = "";
            message.hidden = true;
        }
    };

    const setCaseFieldError = (field, messageText = "") => {
        const message = ensureCaseFieldMessage(field);

        if (isRadioGroupField(field)) {
            Array.from(field).forEach((input) => input.setAttribute("aria-invalid", "true"));
            getChoiceBoxGroup(field)?.classList.add("is-invalid");
        } else {
            field.classList.add("is-invalid");
            field.setAttribute("aria-invalid", "true");
            getCaseDateShell(field)?.classList.add("is-invalid");
        }

        if (message && messageText) {
            message.textContent = messageText;
            message.hidden = false;
        }
    };

    const normalizeCaseStatusKey = (status) => {
        const value = String(status || "").trim().toLowerCase();

        if (value === "cfa" || value === "cfa (call for action)" || value === "call for action" || value === "cfa (certificate to file action)" || value === "certificate to file action" || value === "cfa (certificate of file action)" || value === "certificate of file action") {
            return "cfa";
        }

        return value;
    };

    const addCaseValidationError = (form, errorsByName, name, message) => {
        if (errorsByName.has(name)) {
            return;
        }

        const field = form.elements[name];

        if (!field) {
            return;
        }

        errorsByName.set(name, message);
        setCaseFieldError(field, message);
        expandCaseCollapsibleForField(field);
    };

    const getCaseFormValue = (form, name) => getCaseFieldValue(form.elements[name]).trim();

    const getSettlementDependencyMessage = (dateFiled, initialConfrontation) => {
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

    const validateCaseRules = (form) => {
        applyCaseInputSanitizers(form);

        const errorsByName = new Map();
        const dateFiled = getCaseFormValue(form, "date_filed");
        const initialConfrontation = getCaseFormValue(form, "date_initial_confrontation");
        const settlementAward = getCaseFormValue(form, "date_settlement_award");
        const executionDate = getCaseFormValue(form, "date_execution");
        const agreement = getCaseFormValue(form, "main_point_of_agreement");
        const status = normalizeCaseStatusKey(getCaseFormValue(form, "case_status"));
        const complainantBirthdate = getCaseFormValue(form, "complainant_birthdate");
        const calculatedComplainantAge = calculateAgeFromBirthdate(complainantBirthdate);

        if (form.elements.complainant_age && calculatedComplainantAge !== "") {
            form.elements.complainant_age.value = calculatedComplainantAge;
        }

        requiredCaseFields.forEach((name) => {
            if (getCaseFormValue(form, name) === "") {
                addCaseValidationError(form, errorsByName, name, `${caseFieldLabels[name]} is required.`);
            }
        });

        if (complainantBirthdate && calculatedComplainantAge === "") {
            addCaseValidationError(form, errorsByName, "complainant_birthdate", "Complainant Birthdate must be a valid past date.");
        }

        if (complainantBirthdate && !hasFourDigitDateYear(complainantBirthdate)) {
            addCaseValidationError(form, errorsByName, "complainant_birthdate", "Complainant Birthdate year must be exactly 4 digits.");
        }

        lettersOnlyFields.forEach((name) => {
            const value = getCaseFormValue(form, name);

            if (value !== "" && !isLettersOnly(value)) {
                addCaseValidationError(form, errorsByName, name, `${caseFieldLabels[name]} must contain letters only.`);
            }
        });

        if (getCaseFormValue(form, "complainant_status") !== "" && !validComplainantStatuses.has(getCaseFormValue(form, "complainant_status"))) {
            addCaseValidationError(form, errorsByName, "complainant_status", "Complainant Status must be Single, Married, Widowed, or Separated.");
        }

        Object.entries(exactDigitFields).forEach(([name, length]) => {
            const value = getCaseFormValue(form, name);

            if (value !== "" && !new RegExp(`^\\d{${length}}$`).test(value)) {
                addCaseValidationError(form, errorsByName, name, `${caseFieldLabels[name]} must be exactly ${length} digits.`);
            }
        });

        if (initialConfrontation && !dateFiled) {
            addCaseValidationError(form, errorsByName, "date_initial_confrontation", "Please enter Date Filed first.");
        }

        if (settlementAward) {
            const message = getSettlementDependencyMessage(dateFiled, initialConfrontation);

            if (message) {
                addCaseValidationError(form, errorsByName, "date_settlement_award", message);
            }
        }

        if (executionDate && !settlementAward) {
            addCaseValidationError(form, errorsByName, "date_execution", "Settlement date is required before execution date.");
        }

        if ((status === "settled" || settlementAward) && !agreement) {
            addCaseValidationError(form, errorsByName, "main_point_of_agreement", "Main Point of Agreement is required for settled cases.");
        }

        if (status === "endorsed") {
            if (settlementAward || executionDate) {
                addCaseValidationError(form, errorsByName, "case_status", "Endorsed cases must not have settlement or execution dates.");
            }

            if (!agreement) {
                addCaseValidationError(form, errorsByName, "main_point_of_agreement", "Main Point of Agreement is required for endorsed cases.");
            }
        }

        if (status === "dismissed") {
            if (settlementAward || executionDate) {
                addCaseValidationError(form, errorsByName, "case_status", "Dismissed cases must not have settlement or execution dates.");
            }

            if (!agreement) {
                addCaseValidationError(form, errorsByName, "main_point_of_agreement", "Dismissal reason is required.");
            }
        }

        if (status === "cfa") {
            if (settlementAward || executionDate) {
                addCaseValidationError(form, errorsByName, "case_status", "CFA cases must not have settlement or execution dates.");
            }

            if (!agreement) {
                addCaseValidationError(form, errorsByName, "main_point_of_agreement", "Main Point of Agreement is required for CFA cases.");
            }
        }

        return Array.from(errorsByName.values());
    };

    const updateCaseDateFieldAvailability = (form) => {
        const dateFiled = getCaseFormValue(form, "date_filed");
        const initialConfrontation = getCaseFormValue(form, "date_initial_confrontation");
        const settlementAward = getCaseFormValue(form, "date_settlement_award");
        const initialField = form.elements.date_initial_confrontation;
        const settlementField = form.elements.date_settlement_award;
        const executionField = form.elements.date_execution;

        if (initialField) {
            initialField.disabled = !dateFiled;
        }

        if (settlementField) {
            settlementField.disabled = !dateFiled || !initialConfrontation;
        }

        if (executionField) {
            executionField.disabled = !dateFiled || !initialConfrontation || !settlementAward;
        }
    };

    const showBlockedCaseDateMessage = (form, name) => {
        const field = form.elements[name];
        const dateFiled = getCaseFormValue(form, "date_filed");
        const initialConfrontation = getCaseFormValue(form, "date_initial_confrontation");
        const settlementAward = getCaseFormValue(form, "date_settlement_award");
        let message = "";

        if (!field) {
            return false;
        }

        if (name === "date_initial_confrontation" && !dateFiled) {
            message = "Please enter Date Filed first.";
        } else if (name === "date_settlement_award") {
            message = getSettlementDependencyMessage(dateFiled, initialConfrontation);
        } else if (name === "date_execution" && !settlementAward) {
            message = "Settlement date is required before execution date.";
        }

        if (!message) {
            return false;
        }

        setCaseFieldError(field, message);
        return true;
    };

    const validateCaseForm = (form) => {
        clearCaseFieldErrors(form);
        updateCaseDateFieldAvailability(form);
        const errors = validateCaseRules(form);

        if (errors.length > 0) {
            showCaseModal(
                "error",
                "Validation Error",
                "Please complete all required fields before saving.",
                errors
            );

        }

        return errors.length === 0;
    };

    document.querySelectorAll("[data-case-form]").forEach((form) => {
        form.setAttribute("novalidate", "novalidate");

        form.querySelectorAll("[data-case-collapsible]").forEach((section) => {
            const toggle = section.querySelector("[data-case-collapsible-toggle]");

            setCaseCollapsibleExpanded(section, false);

            toggle?.addEventListener("click", () => {
                setCaseCollapsibleExpanded(section, section.classList.contains("is-collapsed"));
            });
        });

        form.querySelectorAll("[data-age-source]").forEach((birthdateField) => {
            const ageField = form.elements[birthdateField.dataset.ageSource];
            const updateAge = () => {
                if (ageField) {
                    ageField.value = calculateAgeFromBirthdate(birthdateField.value);
                }
            };

            birthdateField.addEventListener("input", updateAge);
            birthdateField.addEventListener("change", updateAge);
            updateAge();
        });

        requiredCaseFields.forEach((name) => {
            const field = form.elements[name];

            if (!field) {
                return;
            }

            const validateField = () => {
                clearCaseFieldErrors(form);
                updateCaseDateFieldAvailability(form);
                validateCaseRules(form);
            };

            if (isRadioGroupField(field)) {
                Array.from(field).forEach((input) => input.addEventListener("change", validateField));
                return;
            }

            field.addEventListener("input", validateField);
            field.addEventListener("change", validateField);
        });

        lettersOnlyFields.forEach((name) => {
            const field = form.elements[name];

            if (!field) {
                return;
            }

            field.addEventListener("input", () => {
                field.value = sanitizeLettersUppercase(field.value);
            });
        });

        Object.entries(exactDigitFields).forEach(([name, length]) => {
            const field = form.elements[name];

            if (!field) {
                return;
            }

            field.addEventListener("input", () => {
                field.value = sanitizeDigits(field.value, length);
            });
        });

        ["date_initial_confrontation", "date_settlement_award", "date_execution", "case_status", "main_point_of_agreement"].forEach((name) => {
            const field = form.elements[name];

            if (!field) {
                return;
            }

            const validateField = () => {
                clearCaseFieldErrors(form);
                updateCaseDateFieldAvailability(form);
                validateCaseRules(form);
            };

            field.addEventListener("input", validateField);
            field.addEventListener("change", validateField);
            field.addEventListener("focus", () => {
                if (showBlockedCaseDateMessage(form, name)) {
                    return;
                }

                validateField();
            });

            getCaseDateShell(field)?.addEventListener("click", () => {
                if (field.disabled) {
                    showBlockedCaseDateMessage(form, name);
                }
            });

            getCaseFieldGroup(field)?.addEventListener("click", () => {
                if (field.disabled) {
                    showBlockedCaseDateMessage(form, name);
                }
            });
        });

        updateCaseDateFieldAvailability(form);

        form.addEventListener("reset", () => {
            window.setTimeout(() => {
                clearCaseFieldErrors(form);
                form.querySelectorAll("[data-age-source]").forEach((birthdateField) => {
                    const ageField = form.elements[birthdateField.dataset.ageSource];

                    if (ageField) {
                        ageField.value = "";
                    }
                });
                form.querySelectorAll("[data-case-collapsible]").forEach((section) => {
                    setCaseCollapsibleExpanded(section, false);
                });
                updateCaseDateFieldAvailability(form);
            }, 0);
        });

        form.addEventListener("submit", async (event) => {
            event.preventDefault();

            if (!validateCaseForm(form)) {
                return;
            }

            const submitButton = form.querySelector('button[type="submit"]');
            const caseNumberInput = form.querySelector("#caseNumber");
            const originalLabel = submitButton ? submitButton.textContent : "";

            if (submitButton) {
                submitButton.disabled = true;
                submitButton.textContent = "Saving...";
            }

            try {
                const response = await fetch(form.action, {
                    method: "POST",
                    body: new FormData(form),
                });
                const responseText = await response.text();
                let data = {};

                try {
                    data = JSON.parse(responseText);
                } catch (parseError) {
                    showCaseModal(
                        "error",
                        "Save Failed",
                        "The server returned an invalid response. Please try again."
                    );
                    return;
                }

                if (!response.ok || !data.success) {
                    const details = Array.isArray(data.errors) ? data.errors : [];

                    showCaseModal(
                        "error",
                        "Save Failed",
                        data.message || "Unable to save case record. Please try again.",
                        details
                    );
                    return;
                }

                const savedAt = new Date().toLocaleString([], {
                    year: "numeric",
                    month: "short",
                    day: "numeric",
                    hour: "numeric",
                    minute: "2-digit",
                });
                const successDetails = [];

                if (data.case_number) {
                    successDetails.push(`Case Number: ${data.case_number}`);
                }

                successDetails.push(`Date saved: ${savedAt}`);
                clearCaseFieldErrors(form);
                form.reset();

                if (caseNumberInput && data.next_case_number) {
                    caseNumberInput.value = data.next_case_number;
                }

                showCaseModal(
                    "success",
                    "Success",
                    "Case record saved successfully.",
                    successDetails
                );

                form.dispatchEvent(new CustomEvent("case:saved", { detail: data }));
            } catch (error) {
                console.error("Add Case AJAX failure:", error);
                showCaseModal(
                    "error",
                    "Save Failed",
                    error.message || "Unable to save case record. Please try again."
                );
            } finally {
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.textContent = originalLabel;
                }
            }
        });
    });
});

