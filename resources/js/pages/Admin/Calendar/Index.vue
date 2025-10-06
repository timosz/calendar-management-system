<script setup lang="ts">
    import { show as bookingShowRoute } from '@/actions/App/Http/Controllers/Admin/BookingController';
    import { events as calendarEventsRoute } from '@/actions/App/Http/Controllers/Admin/CalendarController';
    import AppLayout from '@/layouts/AppLayout.vue';
    import type { EventClickArg } from '@fullcalendar/core';
    import dayGridPlugin from '@fullcalendar/daygrid';
    import interactionPlugin from '@fullcalendar/interaction';
    import timeGridPlugin from '@fullcalendar/timegrid';
    import FullCalendar from '@fullcalendar/vue3';
    import { Head, router } from '@inertiajs/vue3';
    import { ref } from 'vue';

    const calendarOptions = ref({
        plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
        initialView: 'timeGridWeek',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay',
        },
        slotMinTime: '06:00:00',
        slotMaxTime: '22:00:00',
        allDaySlot: true,
        nowIndicator: true,
        editable: false,
        selectable: true,
        selectMirror: true,
        dayMaxEvents: true,
        weekends: true,
        height: 'auto',
        events: async (fetchInfo: any, successCallback: any, failureCallback: any) => {
            try {
                const url = calendarEventsRoute.url({
                    query: {
                        start: fetchInfo.startStr,
                        end: fetchInfo.endStr,
                    },
                });

                const response = await fetch(url);

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const events = await response.json();
                successCallback(events);
            } catch (error) {
                console.error('Error fetching events:', error);
                failureCallback(error);
            }
        },
        eventClick: handleEventClick,
    });

    function handleEventClick(clickInfo: EventClickArg) {
        const eventType = clickInfo.event.extendedProps.type;
        const eventData = clickInfo.event.extendedProps.data;

        console.log('Event clicked:', eventType, eventData);

        // You can add navigation or modal opening here
        // For example, to navigate to edit pages:
        if (eventType === 'booking') {
            router.visit(bookingShowRoute.url(eventData.id));
        }
    }
</script>

<template>
    <AppLayout>
        <Head title="Calendar" />

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-950">
                    <div class="p-6">
                        <div class="mb-6">
                            <h2 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Calendar</h2>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                View all your availabilities, restrictions, and bookings in one place
                            </p>
                        </div>

                        <!-- Legend -->
                        <div class="mb-6 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-900">
                            <h3 class="mb-3 text-sm font-medium text-gray-900 dark:text-gray-100">Legend</h3>
                            <div class="flex flex-wrap gap-4 text-sm">
                                <div class="flex items-center gap-2">
                                    <div class="h-3 w-3 rounded-full bg-green-500"></div>
                                    <span class="text-gray-700 dark:text-gray-300">Available</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="h-3 w-3 rounded-full bg-gray-400 dark:bg-gray-600"></div>
                                    <span class="text-gray-700 dark:text-gray-300">Restricted</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="h-3 w-3 rounded-full bg-blue-500"></div>
                                    <span class="text-gray-700 dark:text-gray-300">Confirmed Booking</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="h-3 w-3 rounded-full bg-orange-500"></div>
                                    <span class="text-gray-700 dark:text-gray-300">Pending Booking</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="h-3 w-3 rounded-full bg-red-500"></div>
                                    <span class="text-gray-700 dark:text-gray-300">Rejected Booking</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="h-3 w-3 rounded-full bg-gray-500"></div>
                                    <span class="text-gray-700 dark:text-gray-300">Cancelled Booking</span>
                                </div>
                            </div>
                        </div>

                        <!-- Calendar -->
                        <div class="calendar-container">
                            <FullCalendar :options="calendarOptions" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style>
    /* FullCalendar custom styles */
    :root {
        --fc-border-color: hsl(0 0% 92.8%);
        --fc-button-bg-color: hsl(0 0% 96.1%);
        --fc-button-border-color: hsl(0 0% 89.8%);
        --fc-button-hover-bg-color: hsl(0 0% 92.1%);
        --fc-button-hover-border-color: hsl(0 0% 85%);
        --fc-button-active-bg-color: hsl(0 0% 9%);
        --fc-button-active-border-color: hsl(0 0% 9%);
        --fc-button-text-color: hsl(0 0% 3.9%);
        --fc-button-active-text-color: hsl(0 0% 98%);
        --fc-today-bg-color: hsl(0 0% 96.1%);
        --fc-neutral-bg-color: hsl(0 0% 100%);
        --fc-neutral-text-color: hsl(0 0% 3.9%);
    }

    .dark {
        --fc-border-color: hsl(0 0% 14.9%);
        --fc-button-bg-color: hsl(0 0% 14.9%);
        --fc-button-border-color: hsl(0 0% 14.9%);
        --fc-button-hover-bg-color: hsl(0 0% 20%);
        --fc-button-hover-border-color: hsl(0 0% 25%);
        --fc-button-active-bg-color: hsl(0 0% 98%);
        --fc-button-active-border-color: hsl(0 0% 98%);
        --fc-button-text-color: hsl(0 0% 98%);
        --fc-button-active-text-color: hsl(0 0% 9%);
        --fc-today-bg-color: hsl(0 0% 10%);
        --fc-neutral-bg-color: hsl(0 0% 3.9%);
        --fc-neutral-text-color: hsl(0 0% 98%);
    }

    .calendar-container {
        min-height: 600px;
    }

    .fc {
        font-family: inherit;
        background-color: var(--fc-neutral-bg-color);
    }

    /* Calendar title */
    .fc .fc-toolbar-title {
        color: var(--fc-neutral-text-color);
        font-weight: 600;
    }

    /* Day headers */
    .fc .fc-col-header-cell-cushion {
        color: var(--fc-neutral-text-color);
        font-weight: 500;
        padding: 8px 4px;
    }

    /* Day numbers in month view */
    .fc .fc-daygrid-day-number {
        color: var(--fc-neutral-text-color);
    }

    /* Time labels in week/day view */
    .fc .fc-timegrid-slot-label-cushion {
        color: var(--fc-neutral-text-color);
    }

    /* Background color for cells */
    .fc .fc-daygrid-day,
    .fc .fc-timegrid-slot {
        background-color: var(--fc-neutral-bg-color);
    }

    /* Events styling */
    .fc-event {
        cursor: pointer;
        border: none;
    }

    .fc-event:hover {
        opacity: 0.85;
    }

    /* Buttons */
    .fc .fc-button-primary {
        background-color: var(--fc-button-bg-color);
        border-color: var(--fc-button-border-color);
        color: var(--fc-button-text-color);
    }

    .fc .fc-button-primary:hover {
        background-color: var(--fc-button-hover-bg-color);
        border-color: var(--fc-button-hover-border-color);
        color: var(--fc-button-text-color);
    }

    .fc .fc-button-primary:not(:disabled):active,
    .fc .fc-button-primary:not(:disabled).fc-button-active {
        background-color: var(--fc-button-active-bg-color);
        border-color: var(--fc-button-active-border-color);
        color: var(--fc-button-active-text-color);
    }

    /* Today's date highlight */
    .fc .fc-daygrid-day.fc-day-today {
        background-color: var(--fc-today-bg-color) !important;
    }

    .fc .fc-timegrid-col.fc-day-today {
        background-color: var(--fc-today-bg-color);
    }

    /* All day slot label */
    .fc .fc-timegrid-axis-cushion {
        color: var(--fc-neutral-text-color);
    }

    /* Remove default box shadow on hover */
    .fc .fc-daygrid-day:hover {
        background-color: var(--fc-neutral-bg-color);
    }

    .fc-theme-standard th {
        background-color: var(--fc-neutral-bg-color);
    }
</style>
