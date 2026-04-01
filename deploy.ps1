$source = "C:\Users\Ameer Hamza\Downloads\public_html\public_html\*"
$destination = "C:\xampp\htdocs\nexorah\"

Write-Host "Copying files from $source to $destination..."
Copy-Item -Path $source -Destination $destination -Recurse -Force
Write-Host "Deployment to XAMPP complete!"
