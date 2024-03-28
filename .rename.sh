find ./ -type f -print0 | xargs -0 perl -pi -e 's/Disciple_Tools_Media/Disciple_Tools_Media/g';
find ./ -type f -print0 | xargs -0 perl -pi -e 's/disciple_tools_media/disciple_tools_media/g';
find ./ -type f -print0 | xargs -0 perl -pi -e 's/disciple-tools-media/disciple-tools-media/g';
find ./ -type f -print0 | xargs -0 perl -pi -e 's/media/media/g';
find ./ -type f -print0 | xargs -0 perl -pi -e 's/Media/Media/g';
mv disciple-tools-plugin-starter-template.php disciple-tools-media.php
# rm .rename.sh
