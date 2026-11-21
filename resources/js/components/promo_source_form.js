// Shared by the section editor and read-only preview controls.
export function promoSourceForm(state) {
    return {
        ...state,
        get isBroad() { return this.placement === this.broadPlacement; },
        get needsCollection() { return this.source === 'collection'; },
        init() {
            this.$watch('placement', () => this.syncPlacement());
            this.syncPlacement();
        },
        syncPlacement() {
            if (this.isBroad) this.layout = 'row';
            else this.source = 'collection';
        },
    };
}
