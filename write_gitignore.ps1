$content = @"
# Agent and Context folders
.agents/
.context/

# Environment variables
.env
.env.*
!.env.example

# Backup files
*.bak
*.tmp
*.swp

# IDE and OS files
.vscode/
.idea/
.DS_Store
Thumbs.db

# Dependencies
vendor/
node_modules/

# Logs
*.log
"@
$content | Out-File -FilePath ".gitignore" -Encoding utf8
