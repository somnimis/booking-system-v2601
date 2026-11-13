x-data="{
    openModal: null,

    userType: @entangle('userType'),
    firstName: @entangle('firstName'),
    lastName: @entangle('lastName'),
    email: @entangle('email'),
    contactNumber: @entangle('contactNumber'),
    organizationName: @entangle('organizationName'),
    schoolId: @entangle('schoolId'),
    eventTitle: @entangle('eventTitle'),
    eventDetails: @entangle('eventDetails'),
    purposeId: @entangle('purposeId'),
    numParticipants: @entangle('numParticipants'),
    numChairs: @entangle('numChairs'),
    numTables: @entangle('numTables'),
    numMicrophones: @entangle('numMicrophones'),
    additionalRequests: @entangle('additionalRequests'),
    cleanupHours: @entangle('cleanupHours'),
    startDate: @entangle('startDate'),
    endDate: @entangle('endDate'),
    startTime: @entangle('startTime'),
    endTime: @entangle('endTime'),
    allDay: @entangle('allDay'),

    init() {
        const saved = JSON.parse(localStorage.getItem('reservationDraft'));
        if (saved) {
            Object.keys(saved).forEach(key => {
                if (this[key] !== undefined && saved[key] !== null) {
                    this[key] = saved[key];
                }
            });
        }

        this.$watch('userType', val => this.saveDraft());
        this.$watch('firstName', val => this.saveDraft());
        this.$watch('lastName', val => this.saveDraft());
        this.$watch('email', val => this.saveDraft());
        this.$watch('contactNumber', val => this.saveDraft());
        this.$watch('organizationName', val => this.saveDraft());
        this.$watch('schoolId', val => this.saveDraft());
        this.$watch('eventTitle', val => this.saveDraft());
        this.$watch('eventDetails', val => this.saveDraft());
        this.$watch('purposeId', val => this.saveDraft());
        this.$watch('numParticipants', val => this.saveDraft());
        this.$watch('numChairs', val => this.saveDraft());
        this.$watch('numTables', val => this.saveDraft());
        this.$watch('numMicrophones', val => this.saveDraft());
        this.$watch('additionalRequests', val => this.saveDraft());
        this.$watch('cleanupHours', val => this.saveDraft());
        this.$watch('startDate', val => this.saveDraft());
        this.$watch('endDate', val => this.saveDraft());
        this.$watch('startTime', val => this.saveDraft());
        this.$watch('endTime', val => this.saveDraft());
        this.$watch('allDay', val => this.saveDraft());
    },

    saveDraft() {
        const draft = {
            userType: this.userType,
            firstName: this.firstName,
            lastName: this.lastName,
            email: this.email,
            contactNumber: this.contactNumber,
            organizationName: this.organizationName,
            schoolId: this.schoolId,
            eventTitle: this.eventTitle,
            eventDetails: this.eventDetails,
            purposeId: this.purposeId,
            numParticipants: this.numParticipants,
            numChairs: this.numChairs,
            numTables: this.numTables,
            numMicrophones: this.numMicrophones,
            additionalRequests: this.additionalRequests,
            cleanupHours: this.cleanupHours,
            startDate: this.startDate,
            endDate: this.endDate,
            startTime: this.startTime,
            endTime: this.endTime,
            allDay: this.allDay
        };
        localStorage.setItem('reservationDraft', JSON.stringify(draft));
    }
}" x-init="$watch('$wire.currentStep', value => { window.scrollTo({ top: 0, behavior: 'smooth' }); })"