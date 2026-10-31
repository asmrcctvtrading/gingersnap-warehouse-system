---

# Auto‑Start Claude Code in VS Code (Windows)

This guide shows three ways to have the `claude` CLI start automatically when you open VS Code:

1. **Custom terminal profile** – opens a terminal that runs `claude` right away.  
2. **VS Code task** – a reusable task you can invoke from the Command Palette or automatically before debugging.  
3. **Launch configuration** – runs the task before a debug session (optional).

All steps assume you already added **Claude Code** to your `PATH` (the PowerShell script you ran earlier).

---

## 1. Add a Custom Terminal Profile

1. Open VS Code → **File → Preferences → Settings** (`Ctrl+,`).
2. Click the **Open Settings (JSON)** icon in the upper‑right corner (or press `Ctrl+Shift+P` → *Preferences: Open Settings (JSON)*).
3. Insert (or merge) a new terminal profile under `terminal.integrated.profiles.windows`:

```jsonc
"terminal.integrated.profiles.windows": {
    "Claude Code": {
        "path": "C:\\Windows\\System32\\WindowsPowerShell\\v1.0\\powershell.exe",
        "args": [
            "-NoLogo",
            "-NoExit",                     // keep terminal open after `claude` launches
            "-Command",
            "claude"                       // run the CLI; replace with any flags you like
        ],
        "icon": "zap"                     // any built‑in VS Code icon (optional)
    }
},
// Make the new profile the default (optional):
"terminal.integrated.defaultProfile.windows": "Claude Code"
```

4. **Save** the `settings.json`.
5. Open a new terminal (`Ctrl+```). You should see a PowerShell prompt that immediately runs `claude` and stays open, ready for your commands.

### Verify

```powershell
# In the new terminal
claude --version
```

You should see the version output (e.g., `my‑combo 1.0`).

---

## 2. Create a VS Code Task (Optional)

If you prefer a **Task** you can run from the Command Palette, add a `tasks.json` file:

1. In the Explorer, create a folder named `.vscode` (if it doesn’t exist).
2. Inside `.vscode`, create `tasks.json` with this content:

```jsonc
{
    "version": "2.0.0",
    "tasks": [
        {
            "label": "Start Claude Code",
            "type": "process",
            "command": "claude",
            "args": [],                     // add any CLI flags here
            "options": {
                "shell": {
                    "executable": "powershell.exe",
                    "args": ["-NoLogo", "-NoExit", "-Command"]
                }
            },
            "presentation": {
                "reveal": "always",
                "panel": "dedicated",
                "clear": false
            },
            "problemMatcher": []            // no problem matcher needed
        }
    ]
}
```

3. **Run the task**:
   - `Ctrl+Shift+P` → *Tasks: Run Task* → select **Start Claude Code**.
   - A new terminal opens and runs `claude`.

### Auto‑Run Before Debugging (Optional)

Add the following to your `.vscode/launch.json` (or create it if missing):

```jsonc
{
    "version": "0.2.0",
    "configurations": [
        {
            "name": "Launch with Claude",
            "type": "node",                // or your debugger type
            "request": "launch",
            "preLaunchTask": "Start Claude Code",
            // ... other launch settings ...
        }
    ]
}
```

Now each time you start a debug session, VS Code will start Claude Code first.

---

## 3. Verification Checklist

| Step | How to verify |
|------|----------------|
| **Terminal profile** | Open a new terminal → it should immediately show the `claude` prompt (`> `). |
| **Task** | Run *Tasks: Run Task → Start Claude Code* → a terminal appears and stays open after `claude` starts. |
| **Launch config** | Press `F5` (or click the green play button) → you should see the Claude task run before the debugger launches. |
| **Version** | In the Claude terminal run `claude --version`; output should match the version you set earlier. |

---

## 4. Example PowerShell Commands (for reference)

- **Open VS Code to the project folder** (run from PowerShell):

  ```powershell
  code C:\Users\Odyssey Jan Pascual\.omniroute
  ```

- **Manually start Claude** (if you ever need it outside VS Code):

  ```powershell
  claude --model my-combo
  ```

- **Check that `claude` is on PATH**:

  ```powershell
  Get-Command claude
  ```

  It should return the full path, e.g., `C:\Users\<User>\.local\bin\claude.exe`.

---

### Quick Recap

1. **Add a terminal profile** in `settings.json` (recommended).
2. **Optional:** create a task (`tasks.json`) and link it to a launch config (`launch.json`).
3. **Verify** by opening a terminal and running `claude --version`.

Now every time you launch VS Code, your Claude Code CLI will be ready to go automatically. Happy coding!
