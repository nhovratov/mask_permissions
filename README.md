# Mask Permissions

This extension adds a backend module, where you can update your mask permissions for individual BE user groups or just
all in one go. No need to fiddle around in the permissions of backend users anymore!

## How to use it

Just install the extension and go to the new module "Mask Permissions". There will be a list of all backend user groups
and an info, if an update is necessary. If so, you can click "grant permissions" and all mask elements will be available
for this group. There is also a button "Grant permissions to all" if all groups should have access.

### Console Command

There is also a console command to update permissions. There is one optional positional parameter for the backend user
group id. Omitting it, will update all user groups.

`maskpermissions:update <beUserGroupId>`
