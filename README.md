# MPS Admin HelpScout Beacon

This WordPress plugin loads the HelpScout Beacon for support in the WP Admin area, but only for users with the Administrator role. It also enables HelpScout Secure Mode, allowing users to access past tickets securely across multiple devices.

## Features
- Only loads the HelpScout Beacon for administrators in the WordPress admin area
- Secure Mode enabled for authenticated ticket access
- Easy integration with your HelpScout Beacon ID and Secure Key

## Installation
1. Upload the `mps-admin-helpscout-beacon.php` file to your site's `/wp-content/plugins/` directory.
2. Activate the plugin from the WordPress admin dashboard.
3. The Beacon will now appear for administrators in the admin area only.

## Configuration
- The Beacon ID and Secure Key are set in the plugin file. Update them if you need to use different credentials.
- To change the user role or location where the Beacon appears, modify the capability check or hook in the plugin code.

## Support
For more information, visit [myprojectsolution.com/mps-care-plan](https://myprojectsolution.com/mps-care-plan)

## License
This project is licensed under the MIT License.
