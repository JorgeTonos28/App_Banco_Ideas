export default function registerTaskTools(Alpine) {
    window.enableTaskBrowserNotifications = async () => {
        if (!('Notification' in window)) {
            window.alert('Este navegador no admite notificaciones nativas.');
            return false;
        }

        const permission = await Notification.requestPermission();
        if (permission !== 'granted') {
            window.alert('El permiso de notificaciones no fue concedido. Puedes usar recordatorios por correo.');
            return false;
        }

        window.dispatchEvent(new CustomEvent('task-browser-notifications-enabled'));
        return true;
    };

    Alpine.data('taskForm', (initial = {}) => ({
        title: initial.title || '',
        description: initial.description || '',
        ideaId: initial.idea_id || '',
        parentTaskId: initial.parent_task_id || '',
        loadedFromAi: false,

        init() {
            const rawDraft = window.sessionStorage.getItem('innovationTaskDraft');
            if (!rawDraft || this.title || this.description) return;

            try {
                const draft = JSON.parse(rawDraft);
                this.title = draft.title || '';
                this.description = draft.description || '';
                this.ideaId = draft.target_idea_id ? String(draft.target_idea_id) : this.ideaId;
                this.parentTaskId = draft.parent_task_id ? String(draft.parent_task_id) : this.parentTaskId;
                this.loadedFromAi = true;
                window.sessionStorage.removeItem('innovationTaskDraft');
            } catch {
                window.sessionStorage.removeItem('innovationTaskDraft');
            }
        },

        clearAiDraft() {
            this.title = '';
            this.description = '';
            this.ideaId = '';
            this.parentTaskId = '';
            this.loadedFromAi = false;
        }
    }));

    Alpine.data('taskBrowserReminders', (options = {}) => ({
        endpoint: options.endpoint,
        intervalId: null,

        init() {
            if (!this.endpoint || !('Notification' in window)) return;
            if (Notification.permission === 'granted') this.start();
            window.addEventListener('task-browser-notifications-enabled', () => this.start());
        },

        start() {
            if (this.intervalId) return;
            this.poll();
            this.intervalId = window.setInterval(() => this.poll(), 60000);
        },

        async poll() {
            if (Notification.permission !== 'granted') return;

            try {
                const response = await fetch(this.endpoint, { headers: { Accept: 'application/json' } });
                if (!response.ok) return;
                const payload = await response.json();
                const shown = this.shownIds();

                (payload.data || []).forEach((reminder) => {
                    if (shown.includes(reminder.id)) return;
                    const notification = new Notification(reminder.title, {
                        body: reminder.message,
                        icon: '/favicon.ico',
                        tag: `task-reminder-${reminder.id}`,
                    });
                    notification.onclick = () => {
                        window.focus();
                        window.location.assign(reminder.url);
                    };
                    shown.push(reminder.id);
                });

                window.localStorage.setItem('shownTaskReminderIds', JSON.stringify(shown.slice(-100)));
            } catch {
                // A transient polling failure should not interrupt the rest of the application.
            }
        },

        shownIds() {
            try {
                const value = JSON.parse(window.localStorage.getItem('shownTaskReminderIds') || '[]');
                return Array.isArray(value) ? value : [];
            } catch {
                return [];
            }
        }
    }));
}
