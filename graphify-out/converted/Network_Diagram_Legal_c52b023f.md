<!-- converted from Network_Diagram_Legal.docx -->

ICONIGLIO BIANCO INC. — HEAD OFFICE NETWORK
How our computers, servers, and internet connect to each other  |  October 2026


# Network Layers Explained
1 — INTERNET  NOW Telecom (primary) and PLDT (backup). Having two ISPs means the office stays online even if one provider has an outage.
2 — SECURITY GATEWAY  The Sophos XG 105 Firewall acts as the security guard for our network. It filters all internet traffic, blocks hackers, and manages both internet connections. WHS 33 (SFP) is the network port that links to the firewall.
3 — MAIN SWITCH (HUB 5)  HUB 5 is the core of the network — like the main hallway in a building. Every device connects through it. It distributes the network signal to all hubs and devices.
4 — FLOOR / ZONE SWITCHES  HUB 1, HUB 2, HUB 3, HUB 4, HUB 6 and HUB 7 are switches that is distributing the LAN to Department Computers.
5 — CONNECTED DEVICES  All the servers, storage units, workstations, and wireless equipment connected directly to HUB 5:
• NAS 1 & NAS 2 — File and backup storage
• WHS 20, WHS 14 — Server / workstation units
• R Pro Server — Recording and application server
• IT System — IT admin workstation
• WHS 23 (Old CCTV) — Legacy CCTV recording PC
• CCTV View — Surveillance camera viewer
• WiFi Asus Router — Act as the DHCP Server of Wifi
• Omada — Provides wireless internet throughout the office

Prepared by:

Odyssey Jan V. Pascual
IT Manager