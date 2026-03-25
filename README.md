📚 ApiCrumbs: The Global Context Archive

This is the official community-maintained library for the ApiCrumbs ecosystem. It serves as the "Source of Truth" for 10,000+ Crumbs used to ground AI reasoning in high-signal, token-efficient data.

🌐 View Live Docs | 🗳️ View Roadmap | 🚀 Request a Crumb

* * * * *

📖 How it Works

The Archive is a structured collection of PHP classes (Crumbs). Each Crumb is categorized by industry vertical (e.g., `Finance`, `Geo`, `Legal`) and is designed to be "Stitched" by the ApiCrumbs Core Engine.

1.  The Manifest: Our `manifest.json` maps every Crumb to its GitHub source.
2.  The Foundry: The `crumb` CLI pings this repository to `install` and `update` your local data senses.
3.  The Docs: Every Pull Request merged here automatically triggers a GitHub Action to rebuild our Static Documentation Site, providing live previews and "Token ROI" stats for every connector. 

* * * * *

🚦 The Live Roadmap (Project Board)

We use GitHub Projects (V2) to manage our development pipeline. Priorities are set by the community using Weighted Reactions: 

-   💡 Backlog: New ideas submitted via `php crumb suggest`.
-   🗳️ Voting Pool: Validated ideas waiting for Roadmap Sponsor (🚀) votes.
-   🔥 Active Sprint: High-priority Crumbs currently in the "Kitchen" (Development).
-   ✅ Shipped: Merged and ready for `php crumb install`.

➔ Explore the Interactive Roadmap

* * * * *

✍️ Contribution Guide

We invite developers and Enterprise corporations to help us reach the 10,000 strong milestone.

To Submit a New Crumb:

1.  Scaffold Locally: Use `php crumb make [Name] [Category]` to ensure the correct namespace.
2.  Verify Quality: Run `php crumb doctor` to check for SSL and Memory safety (XAMPP-proof).
3.  Submit: Use `php crumb submit [Name] [Category]` to automatically open a Pull Request.

* * * * *

📜 Auto-Generated Documentation

Our static site is rebuilt on every `push` to `main`. It provides:

-   Markdown Previews: See exactly what the LLM will see.
-   Dependency Graphs: Visualizing how Crumbs "Stitch" together (e.g., Postcode → Weather).
-   Efficiency Metrics: Real-time "Token Compression" stats for every connector.

* * * * *

💎 Sponsoware Governance

While the code in this archive is Free, the Roadmap is steered by our sponsors.

-   Community Sponsors ($5/mo): Fund the CI/CD "Crumb Doctors" that keep the 10,000+ connectors healthy.
-   Roadmap Sponsors ($25/mo): Use the 🚀 (Rocket) reaction on any Issue to inject 5x Voting Weight into the sprint queue.

Support the Infrastructure on GitHub Sponsors →