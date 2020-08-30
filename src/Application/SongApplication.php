<?php

declare(strict_types=0);

/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Ampache\Application;

use AmpConfig;
use Catalog;
use Ampache\Model\Song;
use Ampache\Module\Util\Ui;

final class SongApplication implements ApplicationInterface
{
    public function run(): void
    {
        Ui::show_header();

        // Switch on the actions
        switch ($_REQUEST['action']) {
            case 'delete':
                if (AmpConfig::get('demo_mode')) {
                    break;
                }

                $song_id = (string) scrub_in($_REQUEST['song_id']);
                show_confirmation(
                    T_('Are You Sure?'),
                    T_('The Song will be deleted'),
                    AmpConfig::get('web_path') . "/song.php?action=confirm_delete&song_id=" . $song_id,
                    1,
                    'delete_song'
                );
                break;
            case 'confirm_delete':
                if (AmpConfig::get('demo_mode')) {
                    break;
                }

                $song = new Song($_REQUEST['song_id']);
                if (!Catalog::can_remove($song)) {
                    debug_event('song', 'Unauthorized to remove the song `.' . $song->id . '`.', 1);
                    Ui::access_denied();

                    return;
                }

                if ($song->remove()) {
                    show_confirmation(T_('No Problem'), T_('Song has been deleted'), AmpConfig::get('web_path'));
                } else {
                    show_confirmation(T_("There Was a Problem"), T_("Couldn't delete this Song."), AmpConfig::get('web_path'));
                }
                break;
            case 'show_lyrics':
                $song = new Song($_REQUEST['song_id']);
                $song->format();
                $song->fill_ext_info();
                $lyrics = $song->get_lyrics();
                require_once Ui::find_template('show_lyrics.inc.php');
                break;
            case 'show_song':
            default:
                $song = new Song($_REQUEST['song_id']);
                $song->format();
                $song->fill_ext_info();
                if (!$song->id) {
                    debug_event('song', 'Requested a song that does not exist', 2);
                    echo T_("You have requested a Song that does not exist.");
                } else {
                    require_once Ui::find_template('show_song.inc.php');
                }
                break;
        } // end data collection

        // Show the Footer
        Ui::show_query_stats();
        Ui::show_footer();
    }
}