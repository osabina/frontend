<?php
/**
  * Upgrace controller for HTML endpoints.
  *
  * @author Jaisen Mathai <jaisen@jmathai.com>
 */
class UpgradeController extends BaseController
{
  public static function upgrade()
  {
    getAuthentication()->requireAuthentication();
    $readmeFiles = getUpgrade()->getUpgradeVersions(array('readme'));
    $readmes = array();
    if(!empty($readmeFiles))
    {
      foreach($readmeFiles as $files)
      {
        foreach($files as $version => $file)
        {
          $readmes[$version] = getTemplate()->get($file);
        }
      }
    }
    $template = sprintf('%s/upgrade.php', getConfig()->get('paths')->templates);
    $body = getTemplate()->get($template, array('readmes' => $readmes, 'currentVersion' => getUpgrade()->getCurrentVersion(), 'lastVersion' => getUpgrade()->getLastVersion()));
    getTheme()->display('template.php', array('body' => $body, 'page' => 'setup'));
  }

  public static function upgradePost()
  {
    getAuthentication()->requireAuthentication();
    getUpgrade()->performUpgrade();
    // Backwards compatibility
    // TODO remove in 2.0
    $basePath = dirname(Epi::getPath('config'));
    $configFile = sprintf('%s/userdata/configs/%s.ini', $basePath, getenv('HTTP_HOST'));
    if(!file_exists($configFile))
      $configFile = sprintf('%s/generated/%s.ini', Epi::getPath('config'), getenv('HTTP_HOST'));
    $config = file_get_contents($configFile);
    // Backwards compatibility
    // TODO remove in 2.0
    if(strstr($config, 'lastCodeVersion="') !== false)
      $config = preg_replace('/lastCodeVersion="\d+\.\d+\.\d+"/', sprintf('lastCodeVersion="%s"', getUpgrade()->getCurrentVersion()), $config);
    else // Before the upgrade code the lastCodeVersion was not in the config template
      $config = sprintf("[site]\nlastCodeVersion=\"%s\"\n\n", getUpgrade()->getCurrentVersion()) . $config;
    file_put_contents($configFile, $config);
    getRoute()->redirect('/');
  }
}
