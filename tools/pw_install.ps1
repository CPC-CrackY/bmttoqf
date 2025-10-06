## Changer la valeur de cette variable pour indiquer le nom de l'exécutable Chromium
[string] $chrome_exe_new_name = 'kromium.exe'

########################################################################################
$env:https_proxy = ''
npm init playwright@latest --legacy-peer-deps
[string] $git_path = (Split-Path -Parent (Get-Command git).Path)
$git_path = $git_path -replace ' ', '` '
Invoke-Expression ("$git_path\..\usr\bin\sed.exe -i ""s@'win'\: \['chrome-win', '${chrome_exe_new_name}'\]@'win'\: \['chrome-win', 'chrome.exe'\]@"" 'node_modules\playwright-core\lib\server\registry\index.js' ")

$env:https_proxy = 'http://vip-users.proxy.edf.fr:3131'
npx playwright install --with-deps chromium firefox
$env:https_proxy = ''

Push-Location ($env:LOCALAPPDATA + '\ms-playwright\' + (Get-ChildItem $env:LOCALAPPDATA/ms-playwright | where -Property name -like chromium-* | Sort-Object -Property name -Descending | select -first 1) + '\chrome-win'); Rename-Item 'chrome.exe' "${chrome_exe_new_name}" -ErrorAction Ignore; Pop-Location
Invoke-Expression ("$git_path\..\usr\bin\sed.exe -i ""s@'win'\: \['chrome-win', 'chrome.exe'\]@'win'\: \['chrome-win', '${chrome_exe_new_name}'\]@"" 'node_modules\playwright-core\lib\server\registry\index.js' ")

Write-Host
Write-Host ">> OK"
