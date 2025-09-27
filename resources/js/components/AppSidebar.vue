<script setup lang="ts">
    import NavFooter from '@/components/NavFooter.vue';
    import NavMain from '@/components/NavMain.vue';
    import NavUser from '@/components/NavUser.vue';
    import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
    import { dashboard } from '@/routes/admin'; // Import from admin routes
    import { index as availabilityPeriodsIndex } from '@/routes/admin/availability-periods'; // Import availability periods route
    import { NavItem } from '@/types';
    import { Link } from '@inertiajs/vue3';
    import { Calendar, Clock, LayoutDashboard } from 'lucide-vue-next';
    import AppLogo from './AppLogo.vue';

    const mainNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutDashboard,
        },
        {
            title: 'Availability Periods',
            href: availabilityPeriodsIndex(),
            icon: Clock,
        },
    ];

    const footerNavItems: NavItem[] = [
        // {
        //     title: 'Settings',
        //     href: profileEdit(),
        //     icon: Settings,
        // },
    ];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo title="Calendar Admin" subtitle="Management System" :icon="Calendar" />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" label="Admin Panel" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
