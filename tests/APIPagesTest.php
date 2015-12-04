<?hh

namespace HHVM\UserDocumentation\Tests;

use HHVM\UserDocumentation\APINavData;
use HHVM\UserDocumentation\NavDataNode;
use HHVM\UserDocumentation\APIDefinitionType;

class APIPagesTest extends \PHPUnit_Framework_TestCase {
  public function apiPages(): array<(string, NavDataNode)> {
    $to_visit = array_values(APINavData::getNavData());
    $out = [];

    while ($node = array_pop($to_visit)) {
      $out[] = tuple($node['urlPath'], $node);
      foreach ($node['children'] as $child) {
        $to_visit[] = $child;
      }
    }
    return $out;
  }

  /**
   * @dataProvider apiPages
   */
  public function testAPIPage(string $_, NavDataNode $node): void {
    $response = \HH\Asio\join(PageLoader::getPage($node['urlPath']));
    $this->assertSame(200, $response->getStatusCode());

    // Top-level pages don't contain their own name in the output - eg 'Classes'
    // is 'Class Reference'
    $blacklist = (new Set(APIDefinitionType::getValues()))
      ->map($x ==> APINavData::getRootNameForType($x));
    if (!$blacklist->contains($node['name'])) {
      $this->assertContains($node['name'], (string) $response->getBody());
    }
  }
}