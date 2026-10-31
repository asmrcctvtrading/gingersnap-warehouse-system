# Claude Code Project: Cozy 2D AI Virtual Office

Act as a senior frontend developer and pixel-art game UI designer. Build a working, interactive, top-down 2D pixel-art virtual office for my AI development environment.

## 1. Art direction

Create a cozy, charming office inspired by pixel-art management simulation games.

**Visual style**
- Top-down view, with the entire office visible on screen.
- Warm wooden desks, tiled floors, office chairs, computers, bookshelves, plants, windows, and soft lighting.
- Five small pixel-art characters, each with a distinct appearance.
- A central whiteboard, task board, and a small server corner.
- A welcoming color palette of warm wood, muted green, cream, and soft blue.
- Crisp pixel edges, consistent sprite scale, and grid-aligned furniture.
- Subtle idle animations and simple work animations.

The result should look like a tiny office simulation game, not a conventional admin dashboard with pixel-art decorations.

## 2. Office layout

Create one compact office containing:
- Planner's desk.
- Developer's workstation.
- QA testing workstation.
- Security Auditor's desk.
- Researcher's desk.
- Shared meeting table.
- Project whiteboard.
- Server rack and network equipment.
- Plants, windows, bookshelves, and decorative objects.

Ensure furniture and characters do not overlap incorrectly. The layout must remain usable at common desktop resolutions.

## 3. Virtual employees

Create five characters:

1. Planner / Architect
2. Developer
3. QA / Debugger
4. Security Auditor
5. Researcher / Codebase Explorer

Each character must support these states:
- Idle
- Working
- Thinking
- Waiting
- Completed
- Error

Show a small status marker and a concise task label above or near each character.

When a character is clicked, open a compact information panel containing the character's role, current task, state, and recent activity.

For the initial version, use deterministic local state. Do not connect to AI providers to animate characters.

## 4. Interaction

Implement:
- Click-to-inspect characters.
- Manual task assignment.
- Start and complete task actions.
- Status changes.
- A task board that can be opened and closed.
- A small activity log.
- Pause/resume for animations.
- Theme and sound settings, with sound disabled by default.
- Local persistence for tasks and settings.

Use meaningful interactions rather than decorative controls that do nothing.

## 5. Technology

Inspect the existing project before making changes.

If no project exists, use:
- React
- TypeScript
- Vite
- CSS or lightweight SVG/canvas rendering for pixel-art graphics.
- Local storage for simple persistence.

Use a small reusable tile and sprite system. Prefer CSS sprites, SVG assets, or locally generated pixel-art assets over large graphics libraries.

Keep the architecture modular but avoid unnecessary abstractions.

Do not introduce a 3D engine or a heavy game framework.

## 6. Token and resource efficiency

This is primarily a local visual application.

- Do not make LLM requests when the application starts.
- Do not poll AI providers continuously.
- Do not automatically spawn agents.
- Do not run autonomous agent loops.
- Do not require API keys for the MVP.
- Do not generate sprites dynamically through AI at runtime.
- Pause animations when appropriate.
- Respect reduced-motion settings.
- Keep dependencies and bundle size small.

The office must function fully even when offline.

## 7. Future compatibility

Design the application so that it can later integrate with my existing Claude Code CLI and OmniRoute configuration.

My current routing alias is `my-combo`, using free providers first and Claude Pro as the fallback.

Do not modify my existing OmniRoute configuration or provider priority.

Do not claim that an agent is genuinely working unless its status is obtained from a verified integration. Clearly distinguish local demonstration states from real execution.

Keep future integration behind a separate adapter layer.

## 8. Implementation and validation

1. Inspect the project folder.
2. Build the office scene and grid.
3. Add furniture and five characters.
4. Implement animations and click interactions.
5. Implement task assignment and local persistence.
6. Add the task board and activity panel.
7. Test the application at desktop and smaller screen sizes.
8. Run available type checks and tests.
9. Fix errors and provide launch instructions.

Do not stop at a screenshot or static mockup. Deliver a functioning application.

Before overwriting existing files or changing configuration, explain the intended change and preserve a backup when appropriate.

## Definition of done

The finished application should feel like entering a cozy little pixel-art office, with five AI employees at their desks. I can click an employee, inspect their status, assign a task, and watch the office respond without spending AI tokens on routine interactions.

Start by inspecting the project directory, then implement the smallest polished, working version.