(function () {
    document.querySelectorAll("[data-transaction-form]").forEach((form) => {
        const typeField = form.querySelector("[data-role='transaction-type']");
        const categoryField = form.querySelector("[data-role='transaction-category']");

        if (!typeField || !categoryField) {
            return;
        }

        const syncCategoryVisibility = () => {
            let selectedStillValid = false;

            Array.from(categoryField.options).forEach((option) => {
                if (!option.dataset.type) {
                    option.hidden = false;
                    option.disabled = false;
                    return;
                }

                const match = option.dataset.type === typeField.value;
                option.hidden = !match;
                option.disabled = !match;

                if (option.selected && match) {
                    selectedStillValid = true;
                }
            });

            if (!selectedStillValid) {
                categoryField.value = "";
            }
        };

        typeField.addEventListener("change", syncCategoryVisibility);
        categoryField.addEventListener("change", () => {
            const selected = categoryField.selectedOptions[0];
            if (selected?.dataset.type) {
                typeField.value = selected.dataset.type;
                syncCategoryVisibility();
            }
        });

        syncCategoryVisibility();
    });
})();
