#!/bin/bash
for img in `ls *.jpg`
do
echo "Converting $img..."
convert "$img" -thumbnail 200x150^ -gravity center -crop 200x150+0+0  +repage thumbs/thumbs_"$img"
done
echo "All Done!"
