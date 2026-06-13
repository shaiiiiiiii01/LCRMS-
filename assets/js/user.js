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
    };

    const requiredCaseFields = Object.keys(caseFieldLabels);

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

            const option = document.createElement("option");
            option.value = fieldValue;
            option.textContent = fieldValue || "Not set";
            option.selected = true;
            field.appendChild(option);
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

    const renderCaseDetails = (body, caseData) => {
        const form = document.createElement("form");
        const grid = document.createElement("div");

        form.className = "case-form readonly-case-form case-details-form";
        form.setAttribute("aria-label", "Read-only case details");
        grid.className = "case-form-grid";

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
                    createCaseDetailField("Case Status", caseData.case_status, { type: "select" }),
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
            )
        );

        form.appendChild(grid);
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

    const clearCaseFieldErrors = (form) => {
        form.querySelectorAll(".is-invalid").forEach((field) => {
            field.classList.remove("is-invalid");
            field.removeAttribute("aria-invalid");
        });
    };

    const setCaseFieldError = (field) => {
        field.classList.add("is-invalid");
        field.setAttribute("aria-invalid", "true");
    };

    const validateCaseForm = (form) => {
        const errors = [];

        clearCaseFieldErrors(form);

        requiredCaseFields.forEach((name) => {
            const field = form.elements[name];

            if (!field || String(field.value || "").trim() !== "") {
                return;
            }

            const message = `${caseFieldLabels[name]} is required.`;
            errors.push(message);
            setCaseFieldError(field);
        });

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

        requiredCaseFields.forEach((name) => {
            const field = form.elements[name];

            if (!field) {
                return;
            }

            field.addEventListener("input", () => {
                if (String(field.value || "").trim() === "") {
                    return;
                }

                field.classList.remove("is-invalid");
                field.removeAttribute("aria-invalid");
            });
        });

        form.addEventListener("reset", () => {
            window.setTimeout(() => {
                clearCaseFieldErrors(form);
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
