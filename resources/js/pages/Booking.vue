<script setup lang="ts">
    import ToastContainer from '@/components/ToastContainer.vue';
    import { useToast } from '@/composables/useToast';
    import { Head, router, useForm, usePage } from '@inertiajs/vue3';
    import dayjs from 'dayjs';
    import { computed, ref } from 'vue';

    // Props from controller
    interface Props {
        availableSlots: {
            date: string;
            slots: Array<{
                start_time: string;
                end_time: string;
                available: boolean;
                reason?: string;
            }>;
        }[];
        currentWeek: number;
        maxWeeks: number;
        debugMode: boolean;
    }

    const props = defineProps<Props>();
    const page = usePage();
    const { success, error } = useToast();

    // State
    const selectedSlot = ref<{ date: string; start: string; end: string } | null>(null);
    const showModal = ref(false);

    // Form data
    const form = useForm({
        client_name: '',
        client_email: '',
        client_phone: '',
        notes: '',
        booking_date: '',
        start_time: '',
        end_time: '',
    });

    // Computed
    const weekLabel = computed(() => {
        const firstDate = props.availableSlots[0]?.date;
        if (!firstDate) return '';

        const start = dayjs(firstDate);
        const end = start.add(6, 'day');
        return `${start.format('MMMM D')} - ${end.format('D, YYYY')}`;
    });

    // Methods
    const openBookingModal = (date: string, start: string, end: string) => {
        selectedSlot.value = { date, start, end };
        showModal.value = true;

        // Pre-fill the hidden fields
        form.booking_date = date;
        form.start_time = start;
        form.end_time = end;
    };

    const closeModal = () => {
        showModal.value = false;
        selectedSlot.value = null;
        form.reset();
        form.clearErrors();
    };

    const submitBooking = () => {
        form.post('/bookings', {
            preserveScroll: true,
            onSuccess: () => {
                closeModal();
                const flashMessage = page.props.flash?.success;
                if (flashMessage) {
                    success(flashMessage);
                }
            },
            onError: (errors) => {
                // Show a general error message for field-specific errors
                const hasFieldErrors = Object.keys(errors).some((key) => key.startsWith('availabilities.'));

                if (hasFieldErrors) {
                    error('Please check the highlighted fields and correct any errors.');
                } else if (Object.keys(errors).length > 0) {
                    // Handle other types of errors
                    const firstErrorKey = Object.keys(errors)[0];
                    const firstErrorMessage = errors[firstErrorKey];
                    error(Array.isArray(firstErrorMessage) ? firstErrorMessage[0] : firstErrorMessage);
                }

                const flashError = page.props.flash?.error;
                if (flashError) {
                    error(flashError);
                }
            },
        });
    };

    const navigateWeek = (direction: 'prev' | 'next') => {
        const newWeek = props.currentWeek + (direction === 'next' ? 1 : -1);
        router.get(
            window.location.pathname,
            {
                week: newWeek,
                debug: props.debugMode ? 1 : undefined,
            },
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    };

    const toggleDebugMode = () => {
        router.get(
            window.location.pathname,
            {
                week: props.currentWeek,
                debug: props.debugMode ? undefined : 1,
            },
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    };

    const getDayName = (dateString: string) => {
        return dayjs(dateString).format('dddd');
    };

    const getFormattedDate = (dateString: string) => {
        return dayjs(dateString).format('MMM D');
    };

    const getModalDateLabel = (dateString: string) => {
        return dayjs(dateString).format('dddd, MMMM D');
    };

    // Dark mode toggle
    const toggleDarkMode = () => {
        document.documentElement.classList.toggle('dark');
    };
</script>

<template>
    <Head title="Book an Appointment" />

    <div class="min-h-screen bg-background">
        <div class="mx-auto max-w-7xl p-4 md:p-8">
            <!-- Header -->
            <div class="mb-6 rounded-lg border bg-card p-6 shadow-sm">
                <!-- Success Message -->
                <!-- <div
                    v-if="$page.props.flash?.success"
                    class="mb-4 rounded-md border border-green-600/20 bg-green-50 p-3 text-sm text-green-700 dark:border-green-400/20 dark:bg-green-950 dark:text-green-400"
                >
                    {{ $page.props.flash.success }}
                </div> -->

                <div class="flex items-start justify-between">
                    <div>
                        <h1 class="mb-2 text-3xl font-bold text-foreground">Book an Appointment</h1>
                        <p class="text-muted-foreground">Select an available time slot to schedule your appointment</p>
                    </div>
                    <div class="flex gap-2">
                        <!-- Debug Mode Toggle -->
                        <button
                            @click="toggleDebugMode"
                            :class="[
                                'rounded-lg border px-3 py-2 text-sm font-medium transition-colors',
                                debugMode
                                    ? 'border-orange-600/20 bg-orange-50 text-orange-700 hover:bg-orange-100 dark:border-orange-400/20 dark:bg-orange-950 dark:text-orange-400 dark:hover:bg-orange-900'
                                    : 'border-input bg-background text-foreground hover:bg-accent hover:text-accent-foreground',
                            ]"
                            :aria-label="debugMode ? 'Disable debug mode' : 'Enable debug mode'"
                        >
                            <span class="flex items-center gap-2">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"
                                    />
                                </svg>
                                {{ debugMode ? 'Debug: ON' : 'Debug: OFF' }}
                            </span>
                        </button>

                        <!-- Dark Mode Toggle -->
                        <button
                            @click="toggleDarkMode"
                            class="rounded-lg border border-input bg-background p-2 text-foreground transition-colors hover:bg-accent hover:text-accent-foreground"
                            aria-label="Toggle dark mode"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"
                                />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Week Navigation -->
            <div class="mb-6 rounded-lg border bg-card p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <button
                        @click="navigateWeek('prev')"
                        :disabled="currentWeek === 1"
                        class="flex items-center gap-2 rounded-lg border border-input bg-background px-4 py-2 text-sm font-medium text-foreground transition-colors hover:bg-accent hover:text-accent-foreground disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        Previous Week
                    </button>

                    <div class="text-center">
                        <h2 class="text-xl font-semibold text-foreground">{{ weekLabel }}</h2>
                        <p class="text-sm text-muted-foreground">Week {{ currentWeek }} of {{ maxWeeks }}</p>
                    </div>

                    <button
                        @click="navigateWeek('next')"
                        :disabled="currentWeek === maxWeeks"
                        class="flex items-center gap-2 rounded-lg border border-input bg-background px-4 py-2 text-sm font-medium text-foreground transition-colors hover:bg-accent hover:text-accent-foreground disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Next Week
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Calendar Grid -->
            <div class="rounded-lg border bg-card p-6 shadow-sm">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-7">
                    <div
                        v-for="day in availableSlots"
                        :key="day.date"
                        class="rounded-lg border border-border"
                        :class="day.slots.length === 0 ? 'bg-muted' : 'bg-card'"
                    >
                        <!-- Day Header -->
                        <div class="border-b border-border p-3" :class="day.slots.length === 0 ? 'bg-muted' : 'bg-accent/50'">
                            <div class="text-sm font-medium" :class="day.slots.length === 0 ? 'text-muted-foreground' : 'text-muted-foreground'">
                                {{ getDayName(day.date) }}
                            </div>
                            <div class="text-lg font-semibold" :class="day.slots.length === 0 ? 'text-muted-foreground' : 'text-foreground'">
                                {{ getFormattedDate(day.date) }}
                            </div>
                        </div>

                        <!-- Time Slots -->
                        <div class="space-y-2 p-2">
                            <template v-if="day.slots.length > 0">
                                <template v-for="slot in day.slots" :key="`${day.date}-${slot.start_time}`">
                                    <button
                                        v-if="slot.available"
                                        @click="openBookingModal(day.date, slot.start_time, slot.end_time)"
                                        class="w-full rounded-md border border-green-600/20 bg-green-50 p-2 text-sm font-medium text-green-700 transition-colors hover:bg-green-100 dark:border-green-400/20 dark:bg-green-950 dark:text-green-400 dark:hover:bg-green-900"
                                    >
                                        {{ slot.start_time }} - {{ slot.end_time }}
                                    </button>
                                    <div
                                        v-else
                                        class="w-full cursor-not-allowed rounded-md border p-2 text-sm"
                                        :class="
                                            slot.reason
                                                ? 'border-border bg-muted text-muted-foreground'
                                                : 'border-red-600/20 bg-red-50 text-red-700 dark:border-red-400/20 dark:bg-red-950 dark:text-red-400'
                                        "
                                    >
                                        <div class="font-medium">{{ slot.start_time }} - {{ slot.end_time }}</div>
                                        <div v-if="slot.reason" class="mt-1 text-xs">{{ slot.reason }}</div>
                                        <div v-else class="mt-1 text-xs">Booked</div>
                                    </div>
                                </template>
                            </template>
                            <p v-else class="py-4 text-center text-sm text-muted-foreground">Not Available</p>
                        </div>
                    </div>
                </div>

                <!-- Legend -->
                <div class="mt-6 border-t border-border pt-6">
                    <div class="flex flex-wrap justify-center gap-4 text-sm">
                        <div class="flex items-center gap-2">
                            <div class="h-4 w-4 rounded border border-green-600/20 bg-green-50 dark:border-green-400/20 dark:bg-green-950"></div>
                            <span class="text-foreground">Available</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="h-4 w-4 rounded border border-red-600/20 bg-red-50 dark:border-red-400/20 dark:bg-red-950"></div>
                            <span class="text-foreground">Booked</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="h-4 w-4 rounded border border-border bg-muted"></div>
                            <span class="text-foreground">Unavailable</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Booking Modal -->
        <Teleport to="body">
            <div
                v-if="showModal"
                @click.self="closeModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
            >
                <div class="w-full max-w-md rounded-lg border border-border bg-card p-6 shadow-xl">
                    <div class="mb-4 flex items-start justify-between">
                        <div>
                            <h3 class="text-xl font-semibold text-foreground">Book Appointment</h3>
                            <p v-if="selectedSlot" class="mt-1 text-sm text-muted-foreground">
                                {{ getModalDateLabel(selectedSlot.date) }} • {{ selectedSlot.start }} - {{ selectedSlot.end }}
                            </p>
                        </div>
                        <button @click="closeModal" class="text-muted-foreground transition-colors hover:text-foreground" :disabled="form.processing">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Error Messages -->
                    <div
                        v-if="form.errors.error"
                        class="mb-4 rounded-md border border-red-600/20 bg-red-50 p-3 text-sm text-red-700 dark:border-red-400/20 dark:bg-red-950 dark:text-red-400"
                    >
                        {{ form.errors.error }}
                    </div>

                    <form @submit.prevent="submitBooking" class="space-y-4">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-foreground">Full Name</label>
                            <input
                                v-model="form.client_name"
                                type="text"
                                required
                                :disabled="form.processing"
                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground ring-offset-background transition-colors placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                placeholder="John Doe"
                            />
                            <p v-if="form.errors.client_name" class="mt-1 text-xs text-red-600 dark:text-red-400">
                                {{ form.errors.client_name }}
                            </p>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-foreground">Email</label>
                            <input
                                v-model="form.client_email"
                                type="email"
                                required
                                :disabled="form.processing"
                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground ring-offset-background transition-colors placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                placeholder="john@example.com"
                            />
                            <p v-if="form.errors.client_email" class="mt-1 text-xs text-red-600 dark:text-red-400">
                                {{ form.errors.client_email }}
                            </p>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-foreground">Phone (Optional)</label>
                            <input
                                v-model="form.client_phone"
                                type="tel"
                                :disabled="form.processing"
                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground ring-offset-background transition-colors placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                placeholder="+30 123 456 7890"
                            />
                            <p v-if="form.errors.client_phone" class="mt-1 text-xs text-red-600 dark:text-red-400">
                                {{ form.errors.client_phone }}
                            </p>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-foreground">Notes (Optional)</label>
                            <textarea
                                v-model="form.notes"
                                rows="3"
                                :disabled="form.processing"
                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground ring-offset-background transition-colors placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                placeholder="Any additional information..."
                            ></textarea>
                            <p v-if="form.errors.notes" class="mt-1 text-xs text-red-600 dark:text-red-400">
                                {{ form.errors.notes }}
                            </p>
                        </div>

                        <div class="flex gap-3 pt-4">
                            <button
                                type="button"
                                @click="closeModal"
                                :disabled="form.processing"
                                class="flex-1 rounded-md border border-input bg-background px-4 py-2 text-sm font-medium text-foreground transition-colors hover:bg-accent hover:text-accent-foreground disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="flex-1 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <span v-if="!form.processing">Book Appointment</span>
                                <span v-else class="flex items-center justify-center gap-2">
                                    <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path
                                            class="opacity-75"
                                            fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                        ></path>
                                    </svg>
                                    Processing...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <ToastContainer />
    </div>
</template>
