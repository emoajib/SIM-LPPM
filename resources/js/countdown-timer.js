export default (config) => ({
    state: 'hidden',
    remaining: 0,
    days: 0,
    hours: 0,
    minutes: 0,
    seconds: 0,
    isUrgent: false,
    isCritical: false,
    interval: null,
    clientOffset: 0,

    init() {
        if (!config || !config.endDate) {
            this.state = 'hidden';
            return;
        }

        this.clientOffset = Date.now() - new Date(config.serverNow).getTime();

        this.updateState();
        this.interval = setInterval(() => this.tick(), 1000);

        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                if (this.interval) {
                    clearInterval(this.interval);
                    this.interval = null;
                }
            } else {
                this.updateState();
                if (!this.interval && this.state !== 'closed') {
                    this.interval = setInterval(() => this.tick(), 1000);
                }
            }
        });
    },

    destroy() {
        if (this.interval) {
            clearInterval(this.interval);
            this.interval = null;
        }
    },

    tick() {
        if (this.state === 'closed') {
            if (this.interval) {
                clearInterval(this.interval);
                this.interval = null;
            }
            return;
        }
        this.updateState();
    },

    updateState() {
        const now = Date.now() - this.clientOffset;
        const end = new Date(config.endDate).getTime();
        const grace = new Date(config.graceEnd).getTime();
        const start = new Date(config.startDate).getTime();

        if (now < start) {
            this.state = 'waiting';
            this.remaining = start - now;
        } else if (now < end) {
            this.state = 'active';
            this.remaining = end - now;
        } else if (now < grace) {
            this.state = 'grace';
            this.remaining = grace - now;
        } else {
            this.state = 'closed';
            this.remaining = 0;
            if (this.interval) {
                clearInterval(this.interval);
                this.interval = null;
            }
        }

        this.isUrgent = this.remaining < 86400000;
        this.isCritical = this.remaining < 3600000;
        this.days = Math.floor(this.remaining / 86400000);
        this.hours = Math.floor((this.remaining % 86400000) / 3600000);
        this.minutes = Math.floor((this.remaining % 3600000) / 60000);
        this.seconds = Math.floor((this.remaining % 60000) / 1000);
    },

    get displayTime() {
        if (this.state === 'closed') return 'Ditutup';
        if (this.days > 0) {
            return `${this.days}h ${String(this.hours).padStart(2, '0')}:${String(this.minutes).padStart(2, '0')}:${String(this.seconds).padStart(2, '0')}`;
        }
        return `${String(this.hours).padStart(2, '0')}:${String(this.minutes).padStart(2, '0')}:${String(this.seconds).padStart(2, '0')}`;
    },
});
