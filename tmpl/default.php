<?php
// No direct access
defined('_JEXEC') or die; ?>
<?php foreach ($servers as $server) : ?>
    <h2><?php echo $server['serverName'] ?></h2>
    <table>
        <thead>
        <tr>
            <th>Driver</th>
            <th>Best lap</th>
            <th>Gap to leader</th>
            <th>Gap interval</th>
            <th>Best S1</th>
            <th>Best S2</th>
            <th>Best S3</th>
            <th>Optimal</th>
            <th>Opt. diff with Best</th>
        </tr>
        </thead>
        <tbody>
				<?php foreach ($server['results'] as $key => $bestResult) : ?>
            <tr>
                <td>
									<?php echo implode(' ', [
										$bestResult->currentDriver->firstName ?? '',
										$bestResult->currentDriver->lastName ?? '',
									]); ?>
                </td>
                <td>
									<?php echo $helper->milisecondsToTimeStap($bestResult->timing->bestLap); ?>
                </td>
                <td>
									<?php if ($key > 0)
									{
										echo $helper->milisecondsToTimeStap($server['results'][$key]->timing->bestLap -
											$server['results'][0]->timing->bestLap);
									} ?>
                </td>
                <td>
									<?php
									if ($key > 0)
									{
										echo $helper->milisecondsToTimeStap($server['results'][$key]->timing->bestLap - $server['results'][$key -
											1]->timing->bestLap);
									}
									?>
                </td>
                <td>
									<?php echo $helper->milisecondsToTimeStap($bestResult->timing->bestSplits[0]); ?>
                </td>
                <td>
									<?php echo $helper->milisecondsToTimeStap($bestResult->timing->bestSplits[1]); ?>
                </td>
                <td>
									<?php echo $helper->milisecondsToTimeStap($bestResult->timing->bestSplits[2]); ?>
                </td>
                <td>
									<?php
									$optimal = $bestResult->timing->bestSplits[0] + $bestResult->timing->bestSplits[1] +
										$bestResult->timing->bestSplits[2];
									echo $helper->milisecondsToTimeStap($optimal);
									?>
                </td>
                <td>
									<?php echo $helper->milisecondsToTimeStap($bestResult->timing->bestLap - $optimal); ?>
                </td>
            </tr>
				<?php endforeach; ?>
        </tbody>
        <caption>Data of <?php echo $server['timestamp'] ?> will be updated
            in <?php echo $server['next_update'] ?></caption>
    </table>
<?php endforeach; ?>