document.addEventListener("DOMContentLoaded", function () {
    
    // 1. Livewire Event Listeners
    document.addEventListener('livewire:init', () => {
        
        // Listen for the conflict modal trigger from the Livewire class
        Livewire.on('open-conflict-modal', () => {
            // The modal is rendered in the Blade via @if($conflictHtml)
            // We just need to show it
            const modalEl = document.getElementById('conflictModal');
            if (modalEl) {
                new bootstrap.Modal(modalEl).show();
            }
        });

        // Listen for events to open Facility/Equipment Modals
        Livewire.on('open-facility-modal', () => {
            const modalEl = document.getElementById('facilityModal');
            if (modalEl) {
                new bootstrap.Modal(modalEl).show();
            }
        });

        Livewire.on('open-equipment-modal', () => {
            const modalEl = document.getElementById('equipmentModal');
            if (modalEl) {
                new bootstrap.Modal(modalEl).show();
            }
        });
    });

    // 2. Cloudinary Upload (Keep this as is)
    // If you add a file input with `onchange="uploadToCloudinary(this)"`
    // you can keep the upload function here.
    
    // 3. Contact number numeric only (Simple DOM listener)
    document.addEventListener("input", function (e) {
        if (e.target.id === "contactNumberField") {
            e.target.value = e.target.value.replace(/\D/g, "");
        }
    });
});