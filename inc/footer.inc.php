		<div id="footer" class="textleft">
		    <p><?php
            if (!empty($settings['vereinsname_lang'])){
                echo htmlspecialchars((string)$settings['vereinsname_lang'],ENT_QUOTES)." | ";
            }
            echo "VereinsSystem ".$settings['version'];?> | By Nicolas Jourdan &amp; Sebastian Fuss | Icons by <a href="http://www.webdesignshock.com/" target="_blank">WebDesignShock</a></p>
        </div>
    </div>
  </body>
</html>