'use strict';

class Score {
    queryScore()
    {
        if (jQuery('#openmusicscorepages').length) {
            var path = window.location.pathname.split("/");
            var id = path[path.length - 1];
            var me = this;
            jQuery.get(
                '/openmusic/api/score/' + id + '/pngs',
                function (data, status) {
                    me.replyScore(data, status);
                }
            )
        }
    }

    replyScore(data, status) {
        if (status != "success") {
            return;
        }
        var i;
        var html = '';
        for(i=0; i < data.length; i++) {
            html += '<img id="scoreimage' + i + '" class="score-image" src="data:image/png;base64, ' + data[0] + '" />\n'
        }
        jQuery('#openmusicscorepages').html(html);
    }

    pageLoaded() {
        this.queryScore();
    }
}

var score = new Score();

Drupal.behaviors.scorelistBehavior = {
    attach: function (context, settings) {
        jQuery('body', context).once('scoredetail').each(function(){
            score.pageLoaded()
        })
    }
};
