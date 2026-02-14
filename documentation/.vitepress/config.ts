import { defineConfig } from "vitepress";

export default defineConfig({
  title: "Starter Backend for Tenant",
  description:
    "Production-ready Laravel starter project with complete CRUD generation, file management, permissions, and real-time features",
  appearance: true,
  lastUpdated: false,
  // ignoreDeadLinks: [
  //   /http:\/\/localhost/,
  //   /http:\/\/127\.0\.0\.1/,
  // ],
  head: [
    ["link", { rel: "icon", href: "/logo.svg" }],
    ["meta", { property: "og:type", content: "website" }],
    ["meta", { property: "og:title", content: "Starter Backend Documentation" }],
    [
      "meta",
      {
        property: "og:description",
        content:
          "Complete Laravel starter project with unified architecture, powerful development tools, and production-ready modules",
      },
    ],
  ],

  themeConfig: {
    logo: "/logo.svg",
    siteTitle: "Starter Backend for Tenant",
    nav: [
      { text: "Home", link: "/" },
      { text: "Installation", link: "/guide/installation" },
      { text: "Get Started", link: "/guide/quick-start" },
      {
        text: "Tools",
        items: [
          { text: "Dynamic CLI", link: "/guide/tools/dynamic-cli" },
          { text: "Export Builder", link: "/guide/tools/export-builder" },
          { text: "Lookup Manager", link: "/guide/tools/lookup-manager" },
          { text: "Media Manager", link: "/guide/tools/media-manager" },
          { text: "Permission Manager", link: "/guide/tools/permission-manager" },
          { text: "Report Builder", link: "/guide/tools/report-builder" },
        ],
      },
      {
        text: "Resources",
        items: [
          { text: "GitHub", link: "https://git.wakeb.tech/WEB-A/starter-backend" },
        ],
      },
    ],

    sidebar: {
      "/guide/": [
        {
          text: "Getting Started",
          collapsed: false,
          items: [
            { text: "Overview", link: "/guide/overview" },
            { text: "Installation", link: "/guide/installation" },
            { text: "Quick Start", link: "/guide/quick-start" },
          ],
        },
        {
          text: "Architecture Overview",
          collapsed: true,
          items: [
            { text: "Architecture", link: "/guide/architecture" },
            { text: "Authentication", link: "/guide/authentication" },
            { text: "Authorization & Policies", link: "/guide/authorization" },
          ],
        },
        {
          text: "Configuration",
          collapsed: true,
          items: [
            { text: "Configuration Overview", link: "/guide/configuration/" },
            { text: "Multi-Tenancy Config", link: "/guide/configuration/multitenancy" },
            { text: "Project Settings", link: "/guide/configuration/project" },
            { text: "Roles Configuration", link: "/guide/configuration/roles" },
          ],
        },
        {
          text: "Multi-Tenancy",
          collapsed: true,
          items: [
            { text: "Overview", link: "/guide/multitenancy/" },
            { text: "Tenant-Aware Models", link: "/guide/multitenancy/models" },
            { text: "Database Migrations", link: "/guide/multitenancy/migrations" },
            { text: "Tenant Management", link: "/guide/multitenancy/tenants" },
          ],
        },
        {
          text: "Services",
          collapsed: true,
          items: [
            { text: "Services Overview", link: "/guide/services/" },
            { text: "Auth Services", link: "/guide/services/auth-services" },
            { text: "Global Services", link: "/guide/services/global-services" },
            { text: "Tenant Services", link: "/guide/services/tenant-services" },
          ],
        },
        {
          text: "Subscriptions",
          collapsed: true,
          items: [
            { text: "Subscription System", link: "/guide/subscriptions/" },
          ],
        },
        {
          text: "Development Tools",
          collapsed: true,
          items: [
            { text: "Tools Overview", link: "/guide/tools/" },
            { text: "Dynamic CLI", link: "/guide/tools/dynamic-cli" },
            { text: "Lookup Manager", link: "/guide/tools/lookup-manager" },
            { text: "Export Builder", link: "/guide/tools/export-builder" },
            { text: "Media Manager", link: "/guide/tools/media-manager" },
            { text: "Permission Manager", link: "/guide/tools/permission-manager" },
            { text: "Report Builder", link: "/guide/tools/report-builder" },
          ],
        },
        {
          text: "Features & Services",
          collapsed: true,
          items: [
            { text: "Real-time with Reverb", link: "/guide/features/reverb" },
            { text: "Background Jobs", link: "/guide/features/jobs" },
            { text: "Events System", link: "/guide/features/events" },
            { text: "Notification System", link: "/guide/features/notifications" },
            { text: "Activity Logging", link: "/guide/features/activity-logging" },
            { text: "Settings Management", link: "/guide/features/settings" },
            { text: "Filters & Scopes", link: "/guide/features/filters-scopes" },
            { text: "Role Service", link: "/guide/features/role-service" },
            { text: "Custom Rules", link: "/guide/features/custom-rules" },
            { text: "Useful Traits", link: "/guide/features/useful-traits" },
          ],
        },
        {
          text: "API Reference",
          link: "/guide/api-reference"
        },
      ],
    },
    search: {
      provider: "local",
    },
    socialLinks: [
      { icon: "github", link: "https://git.wakeb.tech/WEB-A/starter-backend" },
    ],

    footer: {
      message: "Released under the MIT License.",
      copyright: "Copyright © 2025 Starter Backend",
    },
  },

  markdown: {
    lineNumbers: true,
  },
});
