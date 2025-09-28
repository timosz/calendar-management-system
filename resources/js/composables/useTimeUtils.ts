import dayjs from 'dayjs';
import duration from 'dayjs/plugin/duration';

// Extend dayjs with duration plugin
dayjs.extend(duration);

export const useTimeUtils = () => {
    // Base date for time-only calculations
    const BASE_DATE = '2000-01-01';

    /**
     * Format duration between two time strings
     */
    const formatDuration = (startTime: string | null, endTime: string | null): string => {
        if (!startTime || !endTime) return '-';

        const start = dayjs(`${BASE_DATE} ${startTime}`);
        const end = dayjs(`${BASE_DATE} ${endTime}`);
        const diff = dayjs.duration(end.diff(start));

        const hours = diff.hours();
        const minutes = diff.minutes();

        if (hours === 0) {
            return `${minutes} min`;
        }

        if (minutes === 0) {
            return hours === 1 ? '1 hour' : `${hours} hours`;
        }

        return `${hours}h ${minutes}m`;
    };

    /**
     * Calculate hours between two time strings
     */
    const calculateHoursBetween = (startTime: string, endTime: string): number => {
        const start = dayjs(`${BASE_DATE} ${startTime}`);
        const end = dayjs(`${BASE_DATE} ${endTime}`);
        return dayjs.duration(end.diff(start)).asHours();
    };

    return {
        formatDuration,
        calculateHoursBetween,
    };
};
