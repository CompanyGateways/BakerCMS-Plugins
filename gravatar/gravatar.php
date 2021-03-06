<?php
/**
 * name: Gravatar
 * description: Enables Gravatar avatars for users
 * version: 1.0
 * folder: gravatar
 * class: Gravatar
 * type: avatar
 * hooks: avatar_set_avatar, avatar_get_avatar, avatar_show_avatar, avatar_test_avatar
 * author: Nick Ramsay
 * authorurl: http://hotarucms.org/member.php?1-Nick
 *
 * PHP version 5
 *
 * LICENSE: Hotaru CMS is free software: you can redistribute it and/or 
 * modify it under the terms of the GNU General Public License as 
 * published by the Free Software Foundation, either version 3 of 
 * the License, or (at your option) any later version. 
 *
 * Hotaru CMS is distributed in the hope that it will be useful, but WITHOUT 
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or 
 * FITNESS FOR A PARTICULAR PURPOSE. 
 *
 * You should have received a copy of the GNU General Public License along 
 * with Hotaru CMS. If not, see http://www.gnu.org/licenses/.
 * 
 * @category  Content Management System
 * @package   HotaruCMS
 * @author    Nick Ramsay <admin@hotarucms.org>
 * @copyright Copyright (c) 2009, Hotaru CMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link      http://www.hotarucms.org/
 *
 *
 * USAGE: This class hooks into the Avatar class, so is used like this:
 * 
 * $avatar = new Avatar($h, $user_id, $size, $rating);
 * $avatar->getAvatar($h); // returns the avatar for custom display... OR...
 * $avatar->linkAvatar($h); // displays the avatar linked to the user's profile
 * $avatar->wrapAvatar($h); // displays the avatar linked and wrapped in a div (css class: avatar_wrapper)
 *
 * Shortcuts:
 * $h->setAvatar($user_id, $size, $rating);
 * $h->getAvatar(); $h->linkAvatar(); $h->wrapAvatar();
 */

class Gravatar
{
	/* Set $random_default as follows:
	
		0: Off - the default avatar will not be randomized
		
		1: Normal - shows a random avatar per user, per page
					e.g. user "max_99" has the same avatar in all places on the page
		
		2: Extreme - shows a random avatar per user per instance
					e.g. user "max_99" has different avatars on the same page
	*/
	
	private $random_default = 0; 

	
    /**
     * Set global $h vars for this avatar
     *
     * @param $vars array of size, user_id and user_email
     */
    public function avatar_set_avatar($h, $vars)
    {
        $h->vars['avatar_size'] = $vars['size'];
        $h->vars['avatar_rating'] = $vars['rating'];
        $h->vars['avatar_user_id'] = $vars['user_id'];
        $h->vars['avatar_user_name'] = $vars['user_name'];
        $h->vars['avatar_user_email'] = $vars['user_email'];
    }
    
    
    /**
     * return the avatar with no surrounding HTML div
     *
     * @return return the avatar
     */
    public function avatar_test_avatar($h)
    {
        $grav_url = $this->buildGravatarUrl($h->vars['avatar_user_email'], $h->vars['avatar_size'], $h->vars['avatar_rating'], '404');

        $headers = @get_headers($grav_url);
        if (preg_match("|200|", $headers[0])) {
            return $this->buildGravatarImage($grav_url, $h->vars['avatar_size']);
        }
    }
    
    
    /**
     * return the avatar with no surrounding HTML div
     *
     * @return return the avatar
     */
    public function avatar_get_avatar($h)
    {
        $grav_url = $this->buildGravatarUrl($h, $h->vars['avatar_user_email'], $h->vars['avatar_size'], $h->vars['avatar_rating']);
        $img_url = $this->buildGravatarImage($grav_url, $h->vars['avatar_size']);
        return $img_url;
    }
    
    
    /**
     * Build Gravatar image
     *
     * @param string $email - email of avatar user
     * @param int $size - size (1 ~ 512 pixels)
     * @param string $rating - g, pg, r or x
     * @return string - html for image
     */
    public function buildGravatarUrl($h, $email = '', $size = 32, $rating = 'g', $default = '')
    {
        if ($default != '404') {
        
        	// get a random avatar if enabled
        	if ($this->random_default) {
        		$random_avatar = $this->getRandomDefaultAvatar($h);
        		$default_image = BASEURL . "content/plugins/gravatar/images/" . $random_avatar;
        	}
        	
        	// Look in the theme's images folder for a default avatar before using the one in the Gravatar images folder
        	elseif (file_exists(THEMES . THEME . "images/default_80.png"))
        	{
                $default_image = BASEURL . "content/themes/"  . THEME . "images/default_80.png";
            } 
            
            // get the default avatar from the Gravatar images folder
            else 
            { 
                $default_image = BASEURL . "content/plugins/gravatar/images/default_80.png"; 
            }
            
            $default = urlencode($default_image);
        }
        
        $grav_url = "http://www.gravatar.com/avatar/".md5( strtolower($email) ).
            "?d=". $default .
            "&amp;size=" . $size . 
            "&amp;r=" . $rating;
        
        return $grav_url;
    }
    
    
    /**
     * Build Gravatar image
     *
     * @param string $email - email of avatar user
     * @param int $size - size (1 ~ 512 pixels)
     * @param string $rating - g, pg, r or x
     * @return string - html for image
     */
    public function buildGravatarImage($grav_url = '', $size = 32)
    {
        if (!$grav_url) { return false; }
        
        $resized = "style='height: " . $size . "px; width: " . $size . "px'";
                
        $img_url = "<img class='avatar' src='" . $grav_url . "' " . $resized  ." alt='' />";
        return $img_url;
    }


    /**
     * Get a Random Avatar (used when the user doesn'thave gravatar)
     */
	public function getRandomDefaultAvatar($h)
	{
		$user = $h->vars['avatar_user_id'];
		
		// have we already given this user an avatar on this page?
		if (isset($h->vars['avatar_set'][$user]) && ($this->random_default != 2)) {
			return $h->vars['avatar_set'][$user];
		}
		
		// if not, fetch all avatar names (if we don't already have them)
		if (!isset($h->vars['random_avatars'])) {
			$folder = BASE . "content/plugins/gravatar/images/";
			$h->vars['random_avatars'] = getFilenames($folder, $type='short');
		}
		
		// pick a random image
		$avatar = array_rand($h->vars['random_avatars']);
		
		// assign it to this user (only for this page view)
		$h->vars['avatar_set'][$user] = $h->vars['random_avatars'][$avatar];

		return $h->vars['avatar_set'][$user];
	}
}

?>