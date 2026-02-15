import { defineConfig } from "vitepress";

export default defineConfig({
  title: "Admin Dashboard Kit",
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
    ["meta", { property: "og:title", content: "Admin Dashboard Kit Documentation" }],
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
    siteTitle: "Admin Dashboard Kit",
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
          { text: "GitHub", link: "https://github.com/hasanhawary/starter-backend" },
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
          text: "Core Concepts",
          collapsed: true,
          items: [
            { text: "Architecture", link: "/guide/architecture" },
            { text: "Authentication", link: "/guide/authentication" },
            { text: "Authorization & Policies", link: "/guide/authorization" },
          ],
        },
        {
          text: "Configuration & Setup",
          collapsed: true,
          items: [
            { text: "Configuration", link: "/guide/configuration" },
            { text: "Helper Functions", link: "/guide/helpers" },
            { text: "Brand Configuration", link: "/guide/features/brand-configuration" },
          ],
        },
        {
          text: "Core Features",
          collapsed: true,
          items: [
            { text: "Real-time with Reverb", link: "/guide/features/reverb" },
            { text: "Notification System", link: "/guide/features/notifications" },
            { text: "Activity Logging", link: "/guide/features/activity-logging" },
            { text: "Settings Management", link: "/guide/features/settings" },
          ],
        },
        {
          text: "Development & Code",
          collapsed: true,
          items: [
            { text: "Services & Business Logic", link: "/guide/features/services" },
            { text: "Filters & Query Scopes", link: "/guide/features/filters-scopes" },
            { text: "Custom Validation Rules", link: "/guide/features/custom-rules" },
            { text: "Useful Traits", link: "/guide/features/useful-traits" },
            { text: "Enums & Constants", link: "/guide/features/enums" },
            { text: "Mail Classes", link: "/guide/features/mail-classes" },
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
          text: "Database & Models",
          collapsed: true,
          items: [
            { text: "Database Models", link: "/guide/database-models" },
            { text: "Middleware", link: "/guide/middleware" },
            { text: "Error Handling", link: "/guide/error-handling" },
          ],
        },
        {
          text: "Deployment & Operations",
          collapsed: true,
          items: [
            { text: "Deployment", link: "/guide/deployment" },
            { text: "Troubleshooting", link: "/guide/troubleshooting" },
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
      { icon: "github", link: "https://github.com/hasanhawary/starter-backend" },
    ],

    footer: {
      message: "Released under the MIT License.",
      copyright: "Copyright © 2025 Admin Dashboard Kit",
    },
  },

  markdown: {
    lineNumbers: true,
  },
});
