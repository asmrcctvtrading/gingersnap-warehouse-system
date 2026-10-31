<!-- converted from Network_Diagram.docx -->

ILCONIGLIO BIANCO INC. — HEAD OFFICE NETWORK
How our computers, servers, and internet connect to each other  |  October 2026



# Network Layers Explained
1 — INTERNET  PLDT (primary) and NOW Telecom (backup). Having two ISPs means the office stays online even if one provider has an outage.

2 — SECURITY GATEWAY  The Sophos XG 105 Firewall acts as the security guard for our network. It filters all internet traffic, blocks hackers, and manages both internet connections. WHS 33 (SFP) is the network port that links to the firewall.

3 — MAIN SWITCH (HUB 5)  HUB 5 is the core of the network — like the main hallway in a building. Every device connects through it. It distributes the network signal to all hubs and devices.

4 — FLOOR / ZONE SWITCHES  HUB 1 through HUB 7 are smaller switches placed in different areas or floors. They extend the network so more computers and devices can connect.

5 — CONNECTED DEVICES  All the servers, storage units, workstations, and wireless equipment connected directly to HUB 5:
• NAS 1 & NAS 2 — File and backup storage
• WHS 20, WHS 14 — Server / workstation units
• R Pro Server — Recording and application server
• File Server — Central shared file storage
• IT System — IT admin workstation
• WHS 23 (Old CCTV) — Legacy CCTV recording PC
• Q4C View — Surveillance camera viewer
• WiFi Asus Router — Provides wireless internet throughout the office

Generated from hand-drawn network sketch  ·  IT Dept  ·  October 2026