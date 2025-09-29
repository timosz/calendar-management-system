import { setupCalendar } from 'v-calendar';

export default {
    install(app: any) {
        app.use(setupCalendar, {
            // Configure defaults here if needed
        });
    },
};
