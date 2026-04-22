document.addEventListener("DOMContentLoaded", () => {
    const licenseModel = document.getElementById("license_model");
    const maxSeatsField = document.getElementById("max_seats_field");
    const maxSeatsInput = document.getElementById("max_seats");

    if (!licenseModel || !maxSeatsField) {
        return;
    }

    const toggleMaxSeats = () => {
        if (licenseModel.value === "floating") {
            maxSeatsField.classList.remove("hidden");
            if (maxSeatsInput) maxSeatsInput.required = true;
            return;
        }

        maxSeatsField.classList.add("hidden");
        if (maxSeatsInput) {
            maxSeatsInput.required = false;
            maxSeatsInput.value = "";
        }
    };

    licenseModel.addEventListener("change", toggleMaxSeats);
    toggleMaxSeats();
});
