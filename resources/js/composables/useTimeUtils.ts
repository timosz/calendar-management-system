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

    /**
     * Format time string to 12-hour format with AM/PM
     */
    const formatTime = (time: string | null): string | null => {
        if (!time) return null;
        return dayjs(`${BASE_DATE}T${time}`).format('h:mm A');
    };

    /**
     * Format date string to readable format
     */
    const formatDate = (date: string): string => {
        return dayjs(date).format('MMM D, YYYY');
    };

    /**
     * Format date string to short format (no year if current year)
     */
    const formatDateShort = (date: string): string => {
        const d = dayjs(date);
        const isCurrentYear = d.year() === dayjs().year();
        return d.format(isCurrentYear ? 'MMM D' : 'MMM D, YYYY');
    };

    /**
     * Format date range
     */
    const formatDateRange = (startDate: string, endDate: string): string => {
        if (startDate === endDate) {
            return formatDate(startDate);
        }
        return `${formatDate(startDate)} - ${formatDate(endDate)}`;
    };

    /**
     * Format time range
     */
    const formatTimeRange = (startTime: string | null, endTime: string | null, allDay: boolean = false): string => {
        if (allDay || (!startTime && !endTime)) {
            return 'All Day';
        }
        return `${formatTime(startTime)} - ${formatTime(endTime)}`;
    };

    return {
        formatDuration,
        calculateHoursBetween,
        formatTime,
        formatDate,
        formatDateShort,
        formatDateRange,
        formatTimeRange,
    };
};